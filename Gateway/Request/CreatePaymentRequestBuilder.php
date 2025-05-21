<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Gateway\Request\NexiCheckout\SalesDocumentItemsBuilder;
use Nexi\Checkout\Gateway\StringSanitizer;
use Nexi\Checkout\Model\WebhookHandler;
use NexiCheckout\Model\Request\Item;
use NexiCheckout\Model\Request\Payment;
use NexiCheckout\Model\Request\Payment\Address;
use NexiCheckout\Model\Request\Payment\Consumer;
use NexiCheckout\Model\Request\Payment\EmbeddedCheckout;
use NexiCheckout\Model\Request\Payment\HostedCheckout;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;
use NexiCheckout\Model\Request\Payment\PrivatePerson;
use NexiCheckout\Model\Request\Payment\PhoneNumber;
use NexiCheckout\Model\Request\Shared\Notification;
use NexiCheckout\Model\Request\Shared\Notification\Webhook;
use NexiCheckout\Model\Request\Shared\Order as NexiRequestOrder;
use Nexi\Checkout\Gateway\AmountConverter as AmountConverter;

class CreatePaymentRequestBuilder implements BuilderInterface
{
    public const NEXI_PAYMENT_WEBHOOK_PATH = 'nexi/payment/webhook';

    /**
     * @param UrlInterface $url
     * @param Config $config
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param EncryptorInterface $encryptor
     * @param WebhookHandler $webhookHandler
     * @param AmountConverter $amountConverter
     * @param StringSanitizer $stringSanitizer
     */
    public function __construct(
        private readonly UrlInterface $url,
        private readonly Config $config,
        private readonly CountryInformationAcquirerInterface $countryInformationAcquirer,
        private readonly EncryptorInterface $encryptor,
        private readonly WebhookHandler $webhookHandler,
        private readonly AmountConverter $amountConverter,
        private readonly StringSanitizer $stringSanitizer,
    ) {
    }

    /**
     * Build the request for creating a payment
     *
     * @param array $buildSubject
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function build(array $buildSubject): array
    {
        /** @var Order $paymentSubject */
        $paymentSubject = $buildSubject['payment']->getPayment()->getOrder();

        if (!$paymentSubject) {
            $paymentSubject = $buildSubject['payment']->getPayment()->getQuote();
        }

        return [
            'nexi_method' => $this->isEmbedded() ? 'createEmbeddedPayment' : 'createHostedPayment',
            'body'        => [
                'payment' => $this->buildPayment($paymentSubject),
            ]
        ];
    }

    /**
     * Build the Sdk order object
     *
     * @param Quote|Order $order
     *
     * @return NexiRequestOrder
     */
    public function buildOrder(Quote|Order $order): NexiRequestOrder
    {
        return new NexiRequestOrder(
            items    : $this->buildItems($order),
            currency : $order->getBaseCurrencyCode(),
            amount   : $this->amountConverter->convertToNexiAmount($order->getBaseGrandTotal()),
            reference: $order->getIncrementId()
        );
    }

    /**
     * Build the Sdk items object
     *
     * @param Order|Quote $paymentSubject
     *
     * @return OrderItem|array
     */
    public function buildItems(Order|Quote $paymentSubject): OrderItem|array
    {
        /** @var OrderItem $items */
        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = new Item(
                name            : $item->getName(),
                quantity        : (float)$item->getQtyOrdered(),
                unit            : 'pcs',
                unitPrice       : $this->amountConverter->convertToNexiAmount($item->getPrice()),
                grossTotalAmount: $this->amountConverter->convertToNexiAmount($item->getRowTotalInclTax() - $item->getDiscountAmount()), // TODO: calculate discount tax amount based on tax calculation method
                netTotalAmount  : $this->amountConverter->convertToNexiAmount($item->getRowTotal()),
                reference       : $item->getSku(),
                taxRate         : $this->amountConverter->convertToNexiAmount($item->getTaxPercent()),
                taxAmount       : $this->amountConverter->convertToNexiAmount($item->getTaxAmount()),
            );
        }

        if ($paymentSubject instanceof Order) {
            $shippingInfoHolder = $paymentSubject;
        } else {
            $shippingInfoHolder = $paymentSubject->getShippingAddress();
        }

        if ($shippingInfoHolder->getShippingInclTax() ) {
            $items[] = new Item(
                name            : $shippingInfoHolder->getShippingDescription(),
                quantity        : 1,
                unit            : 'pcs',
                unitPrice       : $this->amountConverter->convertToNexiAmount($shippingInfoHolder->getShippingAmount()),
                grossTotalAmount: $this->amountConverter->convertToNexiAmount($shippingInfoHolder->getShippingInclTax()),
                netTotalAmount  : $this->amountConverter->convertToNexiAmount($shippingInfoHolder->getShippingAmount()),
                reference       : SalesDocumentItemsBuilder::SHIPPING_COST_REFERENCE,
                taxRate         : $this->amountConverter->convertToNexiAmount(
                    $shippingInfoHolder->getTaxAmount() / $shippingInfoHolder->getGrandTotal()
                ),
                taxAmount       : $this->amountConverter->convertToNexiAmount($shippingInfoHolder->getShippingTaxAmount()),
            );
        }

        return $items;
    }

    /**
     * Build payment object for a request
     *
     * @param Order|Quote $order
     *
     * @return Payment
     * @throws NoSuchEntityException
     */
    private function buildPayment(Order|Quote $order): Payment
    {
        return new Payment(
            order       : $this->buildOrder($order),
            checkout    : $this->buildCheckout($order),
            notification: new Notification($this->buildWebhooks()),
        );
    }

    /**
     * Build the webhooks for the payment
     *
     * @return array<Webhook>
     *
     * added all for now, we need to check wh
     */
    public function buildWebhooks(): array
    {
        $webhooks = [];
        foreach ($this->webhookHandler->getWebhookProcessors() as $eventName => $processor) {
            $webhookUrl = $this->url->getUrl(self::NEXI_PAYMENT_WEBHOOK_PATH);
            $webhooks[] = new Webhook(
                eventName    : $eventName,
                url          : $webhookUrl,
                authorization: $this->encryptor->hash($this->config->getWebhookSecret())
            );
        }

        return $webhooks;
    }

    /**
     * Build Checkout request object
     *
     * @param Order|Quote $salesObject
     *
     * @return HostedCheckout|EmbeddedCheckout
     * @throws NoSuchEntityException
     */
    public function buildCheckout(Quote|Order $salesObject): HostedCheckout|EmbeddedCheckout
    {
        return $this->isEmbedded() ?
            $this->buildEmbeddedCheckout($salesObject) :
            $this->buildHostedCheckout($salesObject);
    }

    /**
     * Build the consumer object
     *
     * @param Order $order
     *
     * @return Consumer
     * @throws NoSuchEntityException
     */
    private function buildConsumer(Order $order): Consumer
    {
        return new Consumer(
            email          : $order->getCustomerEmail(),
            reference      : $order->getCustomerId(),
            shippingAddress: new Address(
                addressLine1: $this->stringSanitizer->sanitize($order->getShippingAddress()->getStreetLine(1)),
                addressLine2: $this->stringSanitizer->sanitize($order->getShippingAddress()->getStreetLine(2)),
                postalCode  : $order->getShippingAddress()->getPostcode(),
                city        : $this->stringSanitizer->sanitize($order->getShippingAddress()->getCity()),
                country     : $this->getThreeLetterCountryCode(),
            ),
            billingAddress : new Address(
                addressLine1: $this->stringSanitizer->sanitize($order->getBillingAddress()->getStreetLine(1)),
                addressLine2: $this->stringSanitizer->sanitize($order->getBillingAddress()->getStreetLine(2)),
                postalCode  : $order->getBillingAddress()->getPostcode(),
                city        : $order->getBillingAddress()->getCity(),
                country     : $this->getThreeLetterCountryCode(),
            ),
            privatePerson  : new PrivatePerson(
                firstName: $this->stringSanitizer->sanitize($order->getCustomerFirstname()),
                lastName : $this->stringSanitizer->sanitize($order->getCustomerLastname()),
            ),
            phoneNumber    : $this->getNumber($order)
        );
    }

    /**
     * Check integration type
     *
     * @return bool
     */
    public function isEmbedded(): bool
    {
        return $this->config->getIntegrationType() === IntegrationTypeEnum::EmbeddedCheckout->name;
    }

    /**
     * Build Embedded Checkout request object
     * TODO: add consumer data (save email on saving shipping address)
     *
     * @param Quote|Order $salesObject
     *
     * @return EmbeddedCheckout
     */
    public function buildEmbeddedCheckout(Quote|Order $salesObject): EmbeddedCheckout
    {
        return new EmbeddedCheckout(
            url                        : $this->url->getUrl('checkout/onepage/success'),
            termsUrl                   : $this->config->getPaymentsTermsAndConditionsUrl(),
//            consumer                   : $this->buildConsumer($salesObject),
            isAutoCharge               : $this->config->getPaymentAction() == 'authorize_capture',
            merchantHandlesConsumerData: $this->config->getMerchantHandlesConsumerData(),
        );
    }

    /**
     * Build the checkout for hosted integration type
     *
     * @param Quote|Order $salesObject
     *
     * @return HostedCheckout
     * @throws NoSuchEntityException
     */
    public function buildHostedCheckout(Quote|Order $salesObject): HostedCheckout
    {
        return new HostedCheckout(
            returnUrl                  : $this->url->getUrl('checkout/onepage/success'),
            cancelUrl                  : $this->url->getUrl('nexi/hpp/cancelaction'),
            termsUrl                   : $this->config->getWebshopTermsAndConditionsUrl(),
            consumer                   : $this->buildConsumer($salesObject),
            isAutoCharge               : $this->config->getPaymentAction() == 'authorize_capture',
            merchantHandlesConsumerData: $this->config->getMerchantHandlesConsumerData(),
            countryCode                : $this->getThreeLetterCountryCode()
        );
    }

    /**
     * Get the three-letter country code
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getThreeLetterCountryCode(): string
    {
        return $this->countryInformationAcquirer->getCountryInfo(
            $this->config->getCountryCode()
        )->getThreeLetterAbbreviation();
    }

    /**
     * Build phone number object for the payment
     *
     * @param Order $order
     *
     * @return PhoneNumber
     * @throws NumberParseException
     */
    public function getNumber(Order $order): PhoneNumber
    {
        $lib = PhoneNumberUtil::getInstance();

        $number = $lib->parse(
            $order->getShippingAddress()->getTelephone(),
            $order->getShippingAddress()->getCountryId()
        );

        return new PhoneNumber(
            prefix: '+' . $number->getCountryCode(),
            number: (string)$number->getNationalNumber(),
        );
    }
}

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
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
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
        /** @var Order $order */
        $order = $buildSubject['payment']->getPayment()->getOrder();

        return [
            'nexi_method' => 'createHostedPayment',
            'body'        => [
                'payment' => $this->buildPayment($order),
            ]
        ];
    }

    /**
     * Build the Sdk order object
     *
     * @param Order $order
     *
     * @return NexiRequestOrder
     */
    public function buildOrder(Order $order): NexiRequestOrder
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
     * @param Order $order
     *
     * @return OrderItem|array
     */
    private function buildItems(Order $order): OrderItem|array
    {
        /** @var OrderItem $items */
        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = new Item(
                name            : $item->getName(),
                quantity        : (float)$item->getQtyOrdered(),
                unit            : 'pcs',
                unitPrice       : $this->amountConverter->convertToNexiAmount($item->getBasePrice()),
                grossTotalAmount: $this->amountConverter->convertToNexiAmount($item->getBaseRowTotalInclTax()),
                netTotalAmount  : $this->amountConverter->convertToNexiAmount($item->getBaseRowTotal()),
                reference       : $item->getSku(),
                taxRate         : $this->amountConverter->convertToNexiAmount($item->getTaxPercent()),
                taxAmount       : $this->amountConverter->convertToNexiAmount($item->getBaseTaxAmount()),
            );
        }

        if ($order->getShippingAmount()) {
            $items[] = new Item(
                name            : $order->getShippingDescription(),
                quantity        : 1,
                unit            : 'pcs',
                unitPrice       : $this->amountConverter->convertToNexiAmount($order->getBaseShippingAmount()),
                grossTotalAmount: $this->amountConverter->convertToNexiAmount($order->getBaseShippingInclTax()),
                netTotalAmount  : $this->amountConverter->convertToNexiAmount($order->getBaseShippingAmount()),
                reference       : SalesDocumentItemsBuilder::SHIPPING_COST_REFERENCE,
                taxRate         : $this->amountConverter->convertToNexiAmount(
                    $this->getShippingTaxRate($order)
                ),
                taxAmount       : $this->amountConverter->convertToNexiAmount($order->getBaseShippingTaxAmount()),
            );
        }

        return $items;
    }

    /**
     * Build The Sdk payment object
     *
     * @param Order $order
     *
     * @return Payment
     * @throws NoSuchEntityException
     */
    private function buildPayment(Order $order): Payment
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
            $webhookUrl = "https://5b1b-193-65-70-194.ngrok-free.app";
            $webhooks[] = new Webhook(
                eventName    : $eventName,
                url          : $webhookUrl,
                authorization: $this->encryptor->hash($this->config->getWebhookSecret())
            );
        }

        return $webhooks;
    }

    /**
     * Build the checkout object
     *
     * @param Order $order
     *
     * @return HostedCheckout|EmbeddedCheckout
     * @throws NoSuchEntityException
     */
    public function buildCheckout(Order $order): HostedCheckout|EmbeddedCheckout
    {
        if ($this->config->getIntegrationType() == IntegrationTypeEnum::EmbeddedCheckout) {
            return new EmbeddedCheckout(
                url             : $this->url->getUrl('checkout'),
                termsUrl        : $this->config->getPaymentsTermsAndConditionsUrl(),
                merchantTermsUrl: $this->config->getWebshopTermsAndConditionsUrl(),
                consumer        : $this->buildConsumer($order),
            );
        }

        return new HostedCheckout(
            returnUrl                  : $this->url->getUrl('checkout/onepage/success'),
            cancelUrl                  : $this->url->getUrl('nexi/hpp/cancelaction'),
            termsUrl                   : $this->config->getWebshopTermsAndConditionsUrl(),
            consumer                   : $this->buildConsumer($order),
            isAutoCharge               : $this->config->getPaymentAction() == 'authorize_capture',
            merchantHandlesConsumerData: true,
            countryCode                : $this->getThreeLetterCountryCode(),
        );
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

    /**
     * Get shipping tax rate from the order
     *
     * @param Order $order
     *
     * @return float
     */
    private function getShippingTaxRate(Order $order)
    {
        foreach ($order->getExtensionAttributes()?->getItemAppliedTaxes() as $tax) {
            if ($tax->getType() == CommonTaxCollector::ITEM_TYPE_SHIPPING) {
                $appliedTaxes = $tax->getAppliedTaxes();
                return reset($appliedTaxes)->getPercent();
            }
        }

        return 0.0;
    }
}

<?php

namespace Nexi\Checkout\Gateway\Request;

use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Gateway\Request\NexiCheckout\SalesDocumentItemsBuilder;
use Nexi\Checkout\Model\WebhookHandler;
use NexiCheckout\Model\Request\Item;
use NexiCheckout\Model\Request\Payment;
use NexiCheckout\Model\Request\Payment\Address;
use NexiCheckout\Model\Request\Payment\Consumer;
use NexiCheckout\Model\Request\Payment\EmbeddedCheckout;
use NexiCheckout\Model\Request\Payment\HostedCheckout;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;
use NexiCheckout\Model\Request\Payment\PrivatePerson;

class CreatePaymentRequestBuilder implements BuilderInterface
{
    public const NEXI_PAYMENT_WEBHOOK_PATH = '/nexi/payment/webhook';

    /**
     * @param UrlInterface $url
     * @param Config $config
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param EncryptorInterface $encryptor
     * @param WebhookHandler $webhookHandler
     */
    public function __construct(
        private readonly UrlInterface                        $url,
        private readonly Config                              $config,
        private readonly CountryInformationAcquirerInterface $countryInformationAcquirer,
        private readonly EncryptorInterface                  $encryptor,
        private readonly WebhookHandler                      $webhookHandler
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
     * @return Payment\Order
     */
    public function buildOrder(Quote|Order $order): Payment\Order
    {
        return new Payment\Order(
            items    : $this->buildItems($order),
            currency : $order->getBaseCurrencyCode(),
            amount   : (int)($order->getGrandTotal() * 100),
            reference: $order->getIncrementId(),
        );
    }

    /**
     * Build the Sdk items object
     *
     * @param Order|Quote $paymentSubject
     *
     * @return Order\Item|array
     */
    public function buildItems(Order|Quote $paymentSubject): Order\Item|array
    {
        /** @var Order\Item $items */
        foreach ($paymentSubject->getAllVisibleItems() as $item) {
            $items[] = new Item(
                name            : $item->getName(),
                quantity        : (int)$item->getQtyOrdered(),
                unit            : 'pcs',
                unitPrice       : (int)($item->getPrice() * 100),
                grossTotalAmount: (int)($item->getRowTotalInclTax() * 100) - (int)($item->getDiscountAmount() * 100), // TODO: calculate discount tax amount based on tax calculation method
                netTotalAmount  : (int)($item->getRowTotal() * 100),
                reference       : $item->getSku(),
                taxRate         : (int)($item->getTaxPercent() * 100),
                taxAmount       : (int)($item->getTaxAmount() * 100),
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
                unitPrice       : (int)($shippingInfoHolder->getShippingAmount() * 100),
                grossTotalAmount: (int)($shippingInfoHolder->getShippingInclTax() * 100),
                netTotalAmount  : (int)($shippingInfoHolder->getShippingAmount() * 100),
                reference       : SalesDocumentItemsBuilder::SHIPPING_COST_REFERENCE,
                taxRate         : (int)($shippingInfoHolder->getTaxAmount() / $shippingInfoHolder->getGrandTotal() * 100),
                taxAmount       : (int)($shippingInfoHolder->getShippingTaxAmount() * 100),
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
            notification: new Payment\Notification($this->buildWebhooks()),
        );
    }

    /**
     * Build the webhooks for the payment
     *
     * @return array<Payment\Webhook>
     */
    public function buildWebhooks(): array
    {
        $webhooks = [];
        foreach ($this->webhookHandler->getWebhookProcessors() as $eventName => $processor) {
            $baseUrl    = $this->url->getBaseUrl();
            $webhooks[] = new Payment\Webhook(
                eventName    : $eventName,
                url          : $baseUrl . self::NEXI_PAYMENT_WEBHOOK_PATH,
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
    private function buildConsumer($order): Consumer
    {
        return new Consumer(
            email          : $order->getCustomerEmail(),
            reference      : $order->getCustomerId(),
            shippingAddress: new Address(
                addressLine1: $order->getShippingAddress()->getStreetLine(1),
                addressLine2: $order->getShippingAddress()->getStreetLine(2),
                postalCode  : $order->getShippingAddress()->getPostcode(),
                city        : $order->getShippingAddress()->getCity(),
                country     : $this->getThreeLetterCountryCode(),
            ),
            billingAddress : new Address(
                addressLine1: $order->getBillingAddress()->getStreetLine(1),
                addressLine2: $order->getBillingAddress()->getStreetLine(2),
                postalCode  : $order->getBillingAddress()->getPostcode(),
                city        : $order->getBillingAddress()->getCity(),
                country     : $this->getThreeLetterCountryCode(),
            ),
            privatePerson  : new PrivatePerson(
                firstName: $order->getCustomerFirstname(),
                lastName : $order->getCustomerLastname(),
            )
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
}

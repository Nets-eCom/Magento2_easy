<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request;

use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
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
     */
    public function __construct(
        private readonly UrlInterface $url,
        private readonly Config $config,
        private readonly CountryInformationAcquirerInterface $countryInformationAcquirer,
        private readonly EncryptorInterface $encryptor,
        private readonly WebhookHandler $webhookHandler,
        private readonly AmountConverter $amountConverter
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
                unitPrice       : $this->amountConverter->convertToNexiAmount($item->getPrice()),
                grossTotalAmount: $this->amountConverter->convertToNexiAmount($item->getRowTotalInclTax()),
                netTotalAmount  : $this->amountConverter->convertToNexiAmount($item->getRowTotal()),
                reference       : $item->getSku(),
                taxRate         : $this->amountConverter->convertToNexiAmount($item->getTaxPercent()),
                taxAmount       : $this->amountConverter->convertToNexiAmount($item->getTaxAmount()),
            );
        }

        if ($order->getShippingAmount()) {
            $items[] = new Item(
                name            : $order->getShippingDescription(),
                quantity        : 1,
                unit            : 'pcs',
                unitPrice       : $this->amountConverter->convertToNexiAmount($order->getShippingAmount()),
                grossTotalAmount: $this->amountConverter->convertToNexiAmount($order->getShippingInclTax()),
                netTotalAmount  : $this->amountConverter->convertToNexiAmount($order->getShippingAmount()),
                reference       : SalesDocumentItemsBuilder::SHIPPING_COST_REFERENCE,
                taxRate         : $this->amountConverter->convertToNexiAmount(
                    $order->getTaxAmount() / $order->getGrandTotal()
                ),
                taxAmount       : $this->amountConverter->convertToNexiAmount($order->getShippingTaxAmount()),
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
            merchantHandlesConsumerData:(bool)$this->config->getMerchantHandlesConsumerData(),
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

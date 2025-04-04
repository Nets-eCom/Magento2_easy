<?php

namespace Nexi\Checkout\Gateway\Request;

use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Gateway\Request\NexiCheckout\SalesDocumentItemsBuilder;
use NexiCheckout\Model\Request\Item;
use NexiCheckout\Model\Request\Payment;
use NexiCheckout\Model\Request\Payment\Address;
use NexiCheckout\Model\Request\Payment\Consumer;
use NexiCheckout\Model\Request\Payment\EmbeddedCheckout;
use NexiCheckout\Model\Request\Payment\HostedCheckout;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;
use NexiCheckout\Model\Request\Payment\PrivatePerson;
use NexiCheckout\Model\Webhook\EventNameEnum;

class CreatePaymentRequestBuilder implements BuilderInterface
{
    public const NEXI_PAYMENT_WEBHOOK_PATH = '/nexi/payment/webhook';

    /**
     * @param UrlInterface $url
     * @param Config $config
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private readonly UrlInterface                        $url,
        private readonly Config                              $config,
        private readonly CountryInformationAcquirerInterface $countryInformationAcquirer,
        private readonly EncryptorInterface                  $encryptor
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
     * @return Payment\Order
     */
    public function buildOrder(Order $order): Payment\Order
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
     * @param Order $order
     *
     * @return Order\Item|array
     */
    private function buildItems(Order $order): Order\Item|array
    {
        /** @var Order\Item $items */
        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = new Item(
                name            : $item->getName(),
                quantity        : (int)$item->getQtyOrdered(),
                unit            : 'pcs',
                unitPrice       : (int)($item->getPrice() * 100),
                grossTotalAmount: (int)($item->getRowTotalInclTax() * 100),
                netTotalAmount  : (int)($item->getRowTotal() * 100),
                reference       : $item->getSku(),
                taxRate         : (int)($item->getTaxPercent() * 100),
                taxAmount       : (int)($item->getTaxAmount() * 100),
            );
        }

        if ($order->getShippingAmount()) {
            $items[] = new Item(
                name            : $order->getShippingDescription(),
                quantity        : 1,
                unit            : 'pcs',
                unitPrice       : (int)($order->getShippingAmount() * 100),
                grossTotalAmount: (int)($order->getShippingInclTax() * 100),
                netTotalAmount  : (int)($order->getShippingAmount() * 100),
                reference       : SalesDocumentItemsBuilder::SHIPPING_COST_REFERENCE,
                taxRate         : (int)($order->getTaxAmount() / $order->getGrandTotal() * 100),
                taxAmount       : (int)($order->getShippingTaxAmount() * 100),
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
            notification: new Payment\Notification($this->buildWebhooks()),
        );
    }

    /**
     * Build the webhooks for the payment
     *
     * @return array<Payment\Webhook>
     *
     * added all for now, we need to check wh
     */
    public function buildWebhooks(): array
    {
        $webhooks = [];
        foreach (EventNameEnum::cases() as $eventName) {
            $baseUrl    = $this->url->getBaseUrl();
            $webhooks[] = new Payment\Webhook(
                eventName    : $eventName->value,
                url          : $baseUrl . self::NEXI_PAYMENT_WEBHOOK_PATH,
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
            merchantHandlesConsumerData: $this->config->getMerchantHandlesConsumerData(),
            countryCode                : $this->countryInformationAcquirer->getCountryInfo(
                $this->config->getCountryCode()
            )->getThreeLetterAbbreviation(),
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
                country     : $this->countryInformationAcquirer->getCountryInfo(
                    $this->config->getCountryCode()
                )->getThreeLetterAbbreviation(),
            ),
            billingAddress : new Address(
                addressLine1: $order->getBillingAddress()->getStreetLine(1),
                addressLine2: $order->getBillingAddress()->getStreetLine(2),
                postalCode  : $order->getBillingAddress()->getPostcode(),
                city        : $order->getBillingAddress()->getCity(),
                country     : $this->countryInformationAcquirer->getCountryInfo(
                    $this->config->getCountryCode()
                )->getThreeLetterAbbreviation(),
            ),
            privatePerson  : new PrivatePerson(
                firstName: $order->getCustomerFirstname(),
                lastName : $order->getCustomerLastname(),
            )
        );
    }
}

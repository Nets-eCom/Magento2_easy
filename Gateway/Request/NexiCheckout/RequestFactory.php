<?php

namespace Nexi\Checkout\Gateway\Request\NexiCheckout;

use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Url;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Nexi\Checkout\Gateway\Config\Config;
use NexiCheckout\Model\Request\Payment;
use NexiCheckout\Model\Request\Payment\EmbeddedCheckout;
use NexiCheckout\Model\Request\Payment\HostedCheckout;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;
use NexiCheckout\Model\Webhook\EventNameEnum;

class RequestFactory
{
    public const NEXI_PAYMENT_WEBHOOK_PATH = '/nexi/payment/webhook';

    /**
     * Constructor
     *
     * @param Config $config
     * @param Url $url
     * @param EncryptorInterface $encryptor
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     */
    public function __construct(
        private readonly Config                              $config,
        private readonly Url                                 $url,
        private readonly EncryptorInterface                  $encryptor,
        private readonly CountryInformationAcquirerInterface $countryInformationAcquirer,
    ) {
    }

    /**
     * Create request order instance
     *
     * @param Order $order
     *
     * @return Payment\Order
     */
    public function createOrder(Order $order): Payment\Order
    {
        return new \NexiCheckout\Model\Request\Payment\Order(
            items    : $this->getItems($order),
            currency : $order->getBaseCurrencyCode(),
            amount   : $order->getGrandTotal() * 100,
            reference: $order->getIncrementId(),
        );
    }

    public function createCheckout(Order $order): HostedCheckout|EmbeddedCheckout
    {
        if ($this->config->getIntegrationType() == IntegrationTypeEnum::EmbeddedCheckout) {
            return new EmbeddedCheckout(
                url             : $this->url->getUrl('nexi/checkout/success'),
                termsUrl        : $this->config->getPaymentsTermsAndConditionsUrl(),
                merchantTermsUrl: $this->config->getWebshopTermsAndConditionsUrl(),
                consumer        : $this->getConsumer($order),
            );
        }

        return new HostedCheckout(
            returnUrl                  : $this->url->getUrl('nexi/hpp/returnaction'),
            cancelUrl                  : $this->url->getUrl('nexi/hpp/cancelaction'),
            termsUrl                   : $this->config->getWebshopTermsAndConditionsUrl(),
            consumer                   : $this->getConsumer($order),
            isAutoCharge               : $this->config->getPaymentAction() == 'authorize_capture',
            merchantHandlesConsumerData: $this->config->getMerchantHandlesConsumerData(),
            countryCode                : $this->countryInformationAcquirer->getCountryInfo(
                                             $this->config->getCountryCode()
                                         )->getThreeLetterAbbreviation(),
        );
    }

    private function getItems(Order $order): Order\Item|array
    {
        /** @var Order\Item $items */
        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = new \NexiCheckout\Model\Request\Item(
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
            $items[] = new \NexiCheckout\Model\Request\Item(
                name            : $order->getShippingDescription(),
                quantity        : 1,
                unit            : 'pcs',
                unitPrice       : (int)($order->getShippingAmount() * 100),
                grossTotalAmount: (int)($order->getShippingInclTax() * 100),
                netTotalAmount  : (int)($order->getShippingAmount() * 100),
                reference       : $order->getShippingMethod(),
                taxRate         : (int)($order->getTaxAmount() / $order->getShippingInclTax() * 100),
                taxAmount       : (int)($order->getShippingTaxAmount() * 100),
            );
        }

        return $items;
    }

    public function createPayment(Order $order): Payment
    {
        return new Payment(
            order       : $this->createOrder($order),
            checkout    : $this->createCheckout($order),
            notification: new Payment\Notification($this->getWebhooks())
        );
    }

    /**
     * @return array<Payment\Webhook>
     *
     * added all for now, we need to check wh
     */
    public function getWebhooks()
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

    private function getConsumer(Order $order)
    {
        return new Payment\Consumer(
            email          : $order->getCustomerEmail(),
            reference      : $order->getCustomerId(),
            shippingAddress: new Payment\Address(
                                 addressLine1: $order->getShippingAddress()->getStreetLine(1),
                                 addressLine2: $order->getShippingAddress()->getStreetLine(2),
                                 postalCode  : $order->getShippingAddress()->getPostcode(),
                                 city        : $order->getShippingAddress()->getCity(),
                                 country     : $this->getThreeLetterAbbreviation(
                                                   $order->getShippingAddress()->getCountryId()
                                               ),
                             ),
            billingAddress : new Payment\Address(
                                 addressLine1: $order->getBillingAddress()->getStreetLine(1),
                                 addressLine2: $order->getBillingAddress()->getStreetLine(2),
                                 postalCode  : $order->getBillingAddress()->getPostcode(),
                                 city        : $order->getBillingAddress()->getCity(),
                                 country     : $this->getThreeLetterAbbreviation(
                                                   $order->getBillingAddress()->getCountryId()
                                               ),
                             ),

            privatePerson  : new Payment\PrivatePerson(
                                 firstName: $order->getCustomerFirstname(),
                                 lastName : $order->getCustomerLastname(),
                             )
        );
    }

    /**
     * @param Order $order
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getThreeLetterAbbreviation($countyId): string
    {
        return $this->countryInformationAcquirer->getCountryInfo(
            $countyId
        )->getThreeLetterAbbreviation();
    }

    public function getCreditmemoItems(CreditmemoInterface $creditmemo)
    {
        $items = [];
        foreach ($creditmemo->getAllItems() as $item) {
            $items[] = new \NexiCheckout\Model\Request\Item(
                name            : $item->getName(),
                quantity        : (int)$item->getQty(),
                unit            : 'pcs',
                unitPrice       : (int)($item->getPrice() * 100),
                grossTotalAmount: (int)($item->getRowTotalInclTax() * 100),
                netTotalAmount  : (int)($item->getRowTotal() * 100),
                reference       : $item->getSku(),
                taxRate         : (int)($item->getTaxPercent() * 100),
                taxAmount       : (int)($item->getTaxAmount() * 100),
            );
        }

        if ($creditmemo->getShippingInclTax()) {
            $items[] = new \NexiCheckout\Model\Request\Item(
                name            : $creditmemo->getOrder()->getShippingDescription(),
                quantity        : 1,
                unit            : 'pcs',
                unitPrice       : (int)($creditmemo->getShippingAmount() * 100),
                grossTotalAmount: (int)($creditmemo->getShippingInclTax() * 100),
                netTotalAmount  : (int)($creditmemo->getShippingAmount() * 100),
                reference       : $creditmemo->getOrder()->getShippingMethod(),
                taxRate         : (int)($creditmemo->getTaxAmount() / $creditmemo->getShippingInclTax() * 100),
                taxAmount       : (int)($creditmemo->getShippingTaxAmount() * 100),
            );
        }

        return $items;
    }
}

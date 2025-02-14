<?php

namespace Nexi\Checkout\Gateway\Request\NexiCheckout;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Url;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Config\Config;
use NexiCheckout\Model\Request\Payment;
use NexiCheckout\Model\Request\Payment\EmbeddedCheckout;
use NexiCheckout\Model\Request\Payment\HostedCheckout;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;
use NexiCheckout\Model\Webhook\EventNameEnum;

class RequestFactory
{
    const NEXI_PAYMENT_WEBHOOK_PATH = '/nexi/payment/webhook';

    public function __construct(
        private readonly Config             $config,
        private readonly Url                $url,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function createOrder(Order $order): Payment\Order
    {
        return new \NexiCheckout\Model\Request\Payment\Order(
            items   : $this->getItems($order),
            currency: $order->getBaseCurrencyCode(),
            amount  : $order->getGrandTotal() * 100,
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
            returnUrl   : $this->url->getUrl('nexi/hpp/returnaction'),
            cancelUrl   : $this->url->getUrl('nexi/hpp/cancelaction'),
            termsUrl    : $this->config->getWebshopTermsAndConditionsUrl(),
            consumer    : $this->getConsumer($order),
            isAutoCharge: $this->config->getPaymentAction() == 'authorize_capture',
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
                                 country     : $order->getShippingAddress()->getCountryId(),
                             ),
            billingAddress : new Payment\Address(
                                 addressLine1: $order->getBillingAddress()->getStreetLine(1),
                                 addressLine2: $order->getBillingAddress()->getStreetLine(2),
                                 postalCode  : $order->getBillingAddress()->getPostcode(),
                                 city        : $order->getBillingAddress()->getCity(),
                                 country     : $order->getBillingAddress()->getCountryId(),
                             ),
            phoneNumber    : new Payment\PhoneNumber(
                                 prefix: '', //TODO: implement prefix for phone number
                                 number: $order->getBillingAddress()->getTelephone(),
                             ),
            privatePerson  : new Payment\PrivatePerson(
                                 firstName: $order->getCustomerFirstname(),
                                 lastName : $order->getCustomerLastname(),
                             )
        );
    }
}

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
     * @param $order
     *
     * @return Payment\Order
     */
    public function buildOrder($order): Payment\Order
    {
        return new Payment\Order(
            items    : $this->buildItems($order),
            currency : $order->getBaseCurrencyCode(),
            amount   : $order->getGrandTotal() * 100,
            reference: $order->getIncrementId(),
        );
    }

    /**
     * @param Order $order
     *
     * @return Order\Item|array
     */
    public function buildItems($order): Order\Item|array
    {
        /** @var Order\Item $items */
        foreach ($order->getAllVisibleItems() as $item) {
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

        if ($order->getShippingAddress()->getShippingInclTax() ) {
            $items[] = new Item(
                name            : $order->getShippingAddress()->getShippingDescription(),
                quantity        : 1,
                unit            : 'pcs',
                unitPrice       : (int)($order->getShippingAddress()->getShippingAmount() * 100),
                grossTotalAmount: (int)($order->getShippingAddress()->getShippingInclTax() * 100),
                netTotalAmount  : (int)($order->getShippingAddress()->getShippingAmount() * 100),
                reference       : $order->getShippingAddress()->getShippingMethod(),
                taxRate         : (int)($order->getShippingAddress()->getTaxAmount() / $order->getShippingAddress()->getGrandTotal() * 100),
                taxAmount       : (int)($order->getShippingAddress()->getShippingTaxAmount() * 100),
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
    private function buildPayment(Order|\Magento\Quote\Model\Quote $order): Payment
    {
        return new Payment(
            order       : $this->buildOrder($order),
            checkout    : $this->buildCheckout($order),
            notification: new Payment\Notification($this->buildWebhooks()),
        );
    }

    /**
     * TODO: added all for now, we need to check which is actually needed
     *
     * @return array<Payment\Webhook>
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

    private function buildConsumer($order): Consumer
    {
        return new Consumer(
            email          : $order->getBillingAddress()->getEmail(),
            reference      : $order->getCustomerId(),
            shippingAddress: new Address(
                                 addressLine1: $order->getBillingAddress()->getStreetLine(1),
                                 addressLine2: $order->getBillingAddress()->getStreetLine(2),
                                 postalCode  : $order->getBillingAddress()->getPostcode(),
                                 city        : $order->getBillingAddress()->getCity(),
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
                                 firstName: $order->getBillingAddress()->getFirstname(),
                                 lastName : $order->getBillingAddress()->getLastname(),
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
            url                        : $this->url->getUrl('nexi/checkout/success'),
            termsUrl                   : $this->config->getPaymentsTermsAndConditionsUrl(),
//            consumer                   : $this->buildConsumer($salesObject),
            isAutoCharge               : $this->config->getPaymentAction() == 'authorize_capture',
            merchantHandlesConsumerData: $this->config->getMerchantHandlesConsumerData(),
        );
    }

    /**
     * @param Quote|Order $salesObject
     *
     * @return HostedCheckout
     * @throws NoSuchEntityException
     */
    public function buildHostedCheckout(Quote|Order $salesObject): HostedCheckout
    {
        return new HostedCheckout(
            returnUrl                  : $this->url->getUrl('nexi/hpp/returnaction'),
            cancelUrl                  : $this->url->getUrl('nexi/hpp/cancelaction'),
            termsUrl                   : $this->config->getWebshopTermsAndConditionsUrl(),
            consumer                   : $this->buildConsumer($salesObject),
            isAutoCharge               : $this->config->getPaymentAction() == 'authorize_capture',
            merchantHandlesConsumerData: $this->config->getMerchantHandlesConsumerData(),
            countryCode                : $this->countryInformationAcquirer->getCountryInfo(
                                             $this->config->getCountryCode()
                                         )->getThreeLetterAbbreviation(),
        );
    }
}

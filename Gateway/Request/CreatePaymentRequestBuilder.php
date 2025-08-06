<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request;

use libphonenumber\NumberParseException;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Nexi\Checkout\Gateway\AmountConverter as AmountConverter;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Gateway\Request\NexiCheckout\GlobalRequestBuilder;
use Nexi\Checkout\Gateway\Request\NexiCheckout\SalesDocumentItemsBuilder;
use Nexi\Checkout\Gateway\StringSanitizer;
use Nexi\Checkout\Model\Subscription\TotalConfigProvider;
use NexiCheckout\Model\Request\Item;
use NexiCheckout\Model\Request\Payment;
use NexiCheckout\Model\Request\Payment\Address;
use NexiCheckout\Model\Request\Payment\Consumer;
use NexiCheckout\Model\Request\Payment\EmbeddedCheckout;
use NexiCheckout\Model\Request\Payment\HostedCheckout;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;
use NexiCheckout\Model\Request\Payment\PrivatePerson;
use NexiCheckout\Model\Request\Shared\Notification;

class CreatePaymentRequestBuilder implements BuilderInterface
{
    /**
     * CreatePaymentRequestBuilder constructor.
     *
     * @param UrlInterface $url
     * @param Config $config
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param AmountConverter $amountConverter
     * @param StringSanitizer $stringSanitizer
     * @param TotalConfigProvider $totalConfigProvider
     * @param GlobalRequestBuilder $globalRequestBuilder
     */
    public function __construct(
        private readonly UrlInterface                        $url,
        private readonly Config                              $config,
        private readonly CountryInformationAcquirerInterface $countryInformationAcquirer,
        private readonly AmountConverter                     $amountConverter,
        private readonly StringSanitizer                     $stringSanitizer,
        private readonly TotalConfigProvider                 $totalConfigProvider,
        private readonly GlobalRequestBuilder                $globalRequestBuilder
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
            'body' => [
                'payment' => $this->buildPayment($paymentSubject),
            ]
        ];
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
        $items = $this->globalRequestBuilder->getProductsData($paymentSubject);

        if ($paymentSubject instanceof Order) {
            $shippingInfoHolder = $paymentSubject;
        } else {
            $shippingInfoHolder = $paymentSubject->getShippingAddress();
        }

        if ($shippingInfoHolder->getShippingInclTax()) {
            $items[] = new Item(
                name: $shippingInfoHolder->getShippingDescription(),
                quantity: 1,
                unit: 'pcs',
                unitPrice: $this->amountConverter->convertToNexiAmount(
                    $shippingInfoHolder->getBaseShippingAmount()
                ),
                grossTotalAmount: $this->amountConverter->convertToNexiAmount(
                    $shippingInfoHolder->getBaseShippingInclTax()
                ),
                netTotalAmount: $this->amountConverter->convertToNexiAmount(
                    $shippingInfoHolder->getBaseShippingAmount()
                ),
                reference: SalesDocumentItemsBuilder::SHIPPING_COST_REFERENCE,
                taxRate: $this->amountConverter->convertToNexiAmount(
                    $this->globalRequestBuilder->getShippingTaxRate($paymentSubject)
                ),
                taxAmount: $this->amountConverter->convertToNexiAmount(
                    $shippingInfoHolder->getBaseShippingTaxAmount()
                ),
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
            order: $this->globalRequestBuilder->buildOrder($order),
            checkout: $this->buildCheckout($order),
            notification: new Notification($this->globalRequestBuilder->buildWebhooks()),
            subscription: $this->getSubscriptionSetup($order),
            paymentMethodsConfiguration: $this->globalRequestBuilder->buildPaymentMethodsConfiguration(
                $order
            ),
        );
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
     * @param Order|Quote $salesObject
     *
     * @return Consumer
     * @throws NoSuchEntityException|NumberParseException
     */
    private function buildConsumer(Order|Quote $salesObject): Consumer
    {
        $customerId = $salesObject->getCustomerId();
        $shippingAddress = $salesObject->getShippingAddress();
        $billingAddress = $salesObject->getBillingAddress();
        $lastName = $salesObject->getCustomerLastname() ?: $shippingAddress->getLastname();
        $firstName = $salesObject->getCustomerFirstname() ?: $shippingAddress->getFirstname();
        $email = $salesObject->getCustomerEmail() ?: $shippingAddress->getEmail();

        return new Consumer(
            email: $email,
            reference: $customerId,
            shippingAddress: new Address(
                addressLine1: $this->stringSanitizer->sanitize($shippingAddress->getStreetLine(1)),
                addressLine2: $this->stringSanitizer->sanitize($shippingAddress->getStreetLine(2)),
                postalCode: $shippingAddress->getPostcode(),
                city: $this->stringSanitizer->sanitize($shippingAddress->getCity()),
                country: $this->getThreeLetterCountryCode($shippingAddress->getCountryId()),
            ),
            billingAddress: new Address(
                addressLine1: $this->stringSanitizer->sanitize($billingAddress->getStreetLine(1)),
                addressLine2: $this->stringSanitizer->sanitize($billingAddress->getStreetLine(2)),
                postalCode: $billingAddress->getPostcode(),
                city: $this->stringSanitizer->sanitize($billingAddress->getCity()),
                country: $this->getThreeLetterCountryCode($billingAddress->getCountryId()),
            ),
            privatePerson: new PrivatePerson(
                firstName: $this->stringSanitizer->sanitize($firstName),
                lastName: $this->stringSanitizer->sanitize($lastName),
            ),
            phoneNumber: $this->globalRequestBuilder->getNumber($salesObject)
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
     *
     * @param Quote|Order $salesObject
     *
     * @return EmbeddedCheckout
     * @throws NoSuchEntityException
     */
    public function buildEmbeddedCheckout(Quote|Order $salesObject): EmbeddedCheckout
    {
        return new EmbeddedCheckout(
            url: $this->url->getUrl('checkout/onepage/success'),
            termsUrl: $this->config->getPaymentsTermsAndConditionsUrl(),
            consumer: $this->buildConsumer($salesObject),
            isAutoCharge: $this->config->getPaymentAction() == 'authorize_capture',
            merchantHandlesConsumerData: true,
            countryCode: $this->getThreeLetterCountryCode($this->config->getCountryCode()),
        );
    }

    /**
     * Build the checkout for hosted integration type
     *
     * @param Quote|Order $salesObject
     *
     * @return HostedCheckout
     * @throws NoSuchEntityException|NumberParseException
     */
    public function buildHostedCheckout(Quote|Order $salesObject): HostedCheckout
    {
        return new HostedCheckout(
            returnUrl: $this->url->getUrl('checkout/onepage/success'),
            cancelUrl: $this->url->getUrl('nexi/hpp/cancelaction'),
            termsUrl: $this->config->getWebshopTermsAndConditionsUrl(),
            consumer: $this->buildConsumer($salesObject),
            isAutoCharge: $this->config->getPaymentAction() == 'authorize_capture',
            merchantHandlesConsumerData: true,
            countryCode: $this->getThreeLetterCountryCode($this->config->getCountryCode()),
        );
    }

    /**
     * Get the three-letter country code
     *
     * @param string $countryCode
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getThreeLetterCountryCode(string $countryCode): string
    {
        return $this->countryInformationAcquirer->getCountryInfo(
            $countryCode
        )->getThreeLetterAbbreviation();
    }

    /**
     * Set subscription setup for the payment.
     *
     * @return Payment\Subscription|null
     * @throws NoSuchEntityException
     * @throws \DateMalformedStringException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getSubscriptionSetup(): ?Payment\Subscription
    {
        if ($this->totalConfigProvider->isSubscriptionScheduled()
            && $this->totalConfigProvider->isSubscriptionsEnabled()
        ) {

            return new Payment\Subscription(
                subscriptionId: null,
                endDate: new \DateTime((int)date('Y') + 100 . '-01-01'),
                interval: 30,
            );
        }

        return null;
    }
}

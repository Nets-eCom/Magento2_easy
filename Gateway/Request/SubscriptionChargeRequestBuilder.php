<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request;

use libphonenumber\PhoneNumberUtil;
use Magento\Bundle\Model\Product\Price;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Nexi\Checkout\Gateway\AmountConverter as AmountConverter;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Gateway\Request\NexiCheckout\SalesDocumentItemsBuilder;
use Nexi\Checkout\Gateway\StringSanitizer;
use Nexi\Checkout\Model\Subscription\TotalConfigProvider;
use Nexi\Checkout\Model\WebhookHandler;
use NexiCheckout\Model\Request\BulkChargeSubscription;
use NexiCheckout\Model\Request\BulkChargeSubscription\Subscription;
use NexiCheckout\Model\Request\Item;
use NexiCheckout\Model\Request\Payment;
use NexiCheckout\Model\Request\Payment\Address;
use NexiCheckout\Model\Request\Payment\Consumer;
use NexiCheckout\Model\Request\Payment\UnscheduledSubscription;
use NexiCheckout\Model\Request\Payment\EmbeddedCheckout;
use NexiCheckout\Model\Request\Payment\HostedCheckout;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;
use NexiCheckout\Model\Request\Payment\PhoneNumber;
use NexiCheckout\Model\Request\Payment\PrivatePerson;
use NexiCheckout\Model\Request\Shared\Notification;
use NexiCheckout\Model\Request\Shared\Notification\Webhook;
use NexiCheckout\Model\Request\Shared\Order as NexiRequestOrder;

class SubscriptionChargeRequestBuilder implements BuilderInterface
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
     * @param TotalConfigProvider $totalConfigProvider
     */
    public function __construct(
        private readonly UrlInterface                        $url,
        private readonly Config                              $config,
        private readonly CountryInformationAcquirerInterface $countryInformationAcquirer,
        private readonly EncryptorInterface                  $encryptor,
        private readonly WebhookHandler                      $webhookHandler,
        private readonly AmountConverter                     $amountConverter,
        private readonly StringSanitizer                     $stringSanitizer,
        private readonly TotalConfigProvider                 $totalConfigProvider,
    ) {
    }

    /**
     * Build the request for subscription charge.
     *
     * @param array $buildSubject
     * @return Subscription
     */
    public function build(array $buildSubject): Subscription
    {
        /** @var Order $paymentSubject */
        $paymentSubject = $buildSubject['payment']->getPayment()->getOrder();

        if (!$paymentSubject) {
            $paymentSubject = $buildSubject['payment']->getPayment()->getQuote();
        }

        return new Subscription(
            subscriptionId: $paymentSubject->getAdditionalInformation('subscription_id'),
            externalReference: $paymentSubject->getIncrementId(),
            order: $this->buildOrder($paymentSubject)
        );
    }

    private function buildSubscriptionCharge(Order|Quote $order): Payment
    {
        return new Payment(
            order: $this->buildOrder($order),
            checkout: $this->buildCheckout($order),
            notification: new Notification($this->buildWebhooks()),
        );
    }

    public function buildCheckout(Quote|Order $salesObject): HostedCheckout|EmbeddedCheckout
    {
        return $this->isEmbedded() ?
            $this->buildEmbeddedCheckout($salesObject) :
            $this->buildHostedCheckout($salesObject);
    }

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

    public function isEmbedded(): bool
    {
        return $this->config->getIntegrationType() === IntegrationTypeEnum::EmbeddedCheckout->name;
    }

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

    public function buildWebhooks(): array
    {
        $webhooks = [];
        foreach ($this->webhookHandler->getWebhookProcessors() as $eventName => $processor) {
            $webhookUrl = $this->url->getUrl(self::NEXI_PAYMENT_WEBHOOK_PATH);
            $webhooks[] = new Webhook(
                eventName: $eventName,
                url: $webhookUrl,
                authorization: $this->encryptor->hash($this->config->getWebhookSecret())
            );
        }

        return $webhooks;
    }

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
            phoneNumber: $this->getNumber($salesObject)
        );
    }

    public function getNumber(Order|Quote $salesObject): PhoneNumber
    {
        $lib = PhoneNumberUtil::getInstance();

        $telephone = $salesObject->getShippingAddress()->getTelephone();
        $countryId = $salesObject->getShippingAddress()->getCountryId();

        $number = $lib->parse(
            $telephone,
            $countryId
        );

        return new PhoneNumber(
            prefix: '+' . $number->getCountryCode(),
            number: (string)$number->getNationalNumber(),
        );
    }

    public function getThreeLetterCountryCode(string $countryCode): string
    {
        return $this->countryInformationAcquirer->getCountryInfo(
            $countryCode
        )->getThreeLetterAbbreviation();
    }

    /**
     * Build the Sdk order object
     *
     * @param Quote|Order $order
     * @return NexiRequestOrder
     */
    public function buildOrder(Quote|Order $order): NexiRequestOrder
    {
        return new NexiRequestOrder(
            items: $this->buildItems($order),
            currency: $order->getBaseCurrencyCode(),
            amount: $this->amountConverter->convertToNexiAmount($order->getBaseGrandTotal()),
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
        $items = $this->getProductsData($paymentSubject);

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
                    $this->getShippingTaxRate($paymentSubject)
                ),
                taxAmount: $this->amountConverter->convertToNexiAmount(
                    $shippingInfoHolder->getBaseShippingTaxAmount()
                ),
            );
        }

        return $items;
    }

    /**
     * Build payload with order items data based on product type
     *
     * @param Order|Quote $paymentSubject
     * @return array
     */
    public function getProductsData(Order|Quote $paymentSubject): array
    {
        $items = [];
        /** @var OrderItem|Quote\Item $item */
        foreach ($paymentSubject->getAllVisibleItems() as $item) {

            if ($item->getParentItem()) {
                continue;
            }

            switch ($item->getProductType()) {
                case ConfigurableType::TYPE_CODE:
                    $children = $this->getChildren($item);
                    foreach ($children as $childItem) {
                        $base = $this->createItemBaseData($childItem);
                        $enriched = $this->appendPriceData($base, $item);
                        $items[] = $this->createFinalItem($enriched);
                    }
                    break;
                case BundleType::TYPE_CODE:
                    $isDynamicPrice = $item->getProduct()->getPriceType() == Price::PRICE_TYPE_DYNAMIC;
                    $children = $this->getChildren($item);
                    if ($isDynamicPrice) {
                        foreach ($children as $childItem) {
                            $base = $this->createItemBaseData($childItem);
                            $enriched = $this->appendPriceData($base, $childItem);
                            $items[] = $this->createFinalItem($enriched);
                        }
                    } else {
                        $base = $this->createItemBaseData($item);
                        $enriched = $this->appendPriceData($base, $item);
                        $items[] = $this->createFinalItem($enriched);
                    }
                    break;
                default:
                    $base = $this->createItemBaseData($item);
                    $enriched = $this->appendPriceData($base, $item);
                    $items[] = $this->createFinalItem($enriched);
            }
        }

        return $items;
    }
}

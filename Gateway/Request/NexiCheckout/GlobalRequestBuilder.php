<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request\NexiCheckout;

use libphonenumber\PhoneNumberUtil;
use Magento\Bundle\Model\Product\Price;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use Nexi\Checkout\Gateway\AmountConverter as AmountConverter;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\WebhookHandler;
use NexiCheckout\Model\Request\Item;
use NexiCheckout\Model\Request\Payment\PhoneNumber;
use NexiCheckout\Model\Request\Shared\Notification\Webhook;
use NexiCheckout\Model\Request\Shared\Order as NexiRequestOrder;

class GlobalRequestBuilder
{
    public const NEXI_PAYMENT_WEBHOOK_PATH = 'nexi/payment/webhook';

    /**
     * GlobalRequestBuilder constructor.
     *
     * @param UrlInterface $url
     * @param Config $config
     * @param EncryptorInterface $encryptor
     * @param WebhookHandler $webhookHandler
     * @param AmountConverter $amountConverter
     */
    public function __construct(
        private readonly UrlInterface       $url,
        private readonly Config             $config,
        private readonly EncryptorInterface $encryptor,
        private readonly WebhookHandler     $webhookHandler,
        private readonly AmountConverter    $amountConverter,
    ) {
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
     * Get shipping tax rate from the order
     *
     * @param Order|Quote $paymentSubject
     *
     * @return float
     */
    public function getShippingTaxRate(Order|Quote $paymentSubject)
    {
        if ($paymentSubject instanceof Order) {
            foreach ($paymentSubject->getExtensionAttributes()?->getItemAppliedTaxes() as $tax) {
                if ($tax->getType() == CommonTaxCollector::ITEM_TYPE_SHIPPING) {
                    $appliedTaxes = $tax->getAppliedTaxes();
                    return reset($appliedTaxes)->getPercent();
                }
            }
        }
        if ($paymentSubject instanceof Quote) {
            if (!(float)$paymentSubject->getShippingAddress()->getBaseShippingAmount()) {
                return 0.0;
            }
            $shippingTaxRate = $paymentSubject->getShippingAddress()->getBaseShippingTaxAmount() /
                $paymentSubject->getShippingAddress()->getBaseShippingAmount() * 100;
            if ($shippingTaxRate) {
                return $shippingTaxRate;
            }
        }

        return 0.0;
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

    /**
     * Create the nexi SDK item
     *
     * @param array $data
     * @return Item
     */
    private function createFinalItem(array $data): Item
    {
        return new Item(
            name: $data['name'],
            quantity: $data['quantity'],
            unit: $data['unit'],
            unitPrice: $data['unitPrice'],
            grossTotalAmount: $data['grossTotalAmount'],
            netTotalAmount: $data['netTotalAmount'],
            reference: $data['reference'],
            taxRate: $data['taxRate'],
            taxAmount: $data['taxAmount'],
        );
    }

    /**
     * Creates base data array for an item including name, SKU, quantity, and unit.
     *
     * @param mixed $item
     * @return array
     */
    public function createItemBaseData(mixed $item): array
    {
        return [
            'name' => $item->getName(),
            'reference' => $item->getSku(),
            'quantity' => $this->getQuantity($item),
            'unit' => 'pcs'
        ];
    }

    /**
     * Get children items of a given order item or quote item.
     *
     * @param OrderItem|Quote\Item $item
     *
     * @return array|Quote\Item\AbstractItem[]
     */
    public function getChildren(OrderItem|Quote\Item $item): array
    {
        $children = $item instanceof OrderItem ? $item->getChildrenItems() : $item->getChildren();

        return $children;
    }

    /**
     * Returns the quantity of the item.
     *
     * @param mixed $item
     *
     * @return float
     */
    public function getQuantity(mixed $item): float
    {
        $qtyOrdered = $item instanceof OrderItem ? $item->getQtyOrdered() : $item->getQty();

        return (float)$qtyOrdered;
    }

    /**
     * Appends pricing and tax data to the given item data array.
     *
     * @param array $data
     * @param mixed $item
     * @return array
     */
    private function appendPriceData(array $data, mixed $item): array
    {
        $data['unitPrice'] = $this->amountConverter->convertToNexiAmount(
            $item->getBasePrice() - $item->getBaseDiscountAmount() / $this->getQuantity($item)
        );
        $data['grossTotalAmount'] = $this->amountConverter->convertToNexiAmount(
            $item->getBaseRowTotal() - $item->getBaseDiscountAmount() + $item->getBaseTaxAmount()
        );
        $data['netTotalAmount'] = $this->amountConverter->convertToNexiAmount(
            $item->getBaseRowTotal() - $item->getBaseDiscountAmount()
        );
        $data['taxRate'] = $this->amountConverter->convertToNexiAmount($item->getTaxPercent());
        $data['taxAmount'] = $this->amountConverter->convertToNexiAmount($item->getBaseTaxAmount());

        return $data;
    }
}

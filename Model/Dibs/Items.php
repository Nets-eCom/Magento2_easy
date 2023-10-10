<?php

namespace Dibs\EasyCheckout\Model\Dibs;

use Dibs\EasyCheckout\Helper\Data;
use Dibs\EasyCheckout\Model\CheckoutException;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\OrderItem;
use Dibs\EasyCheckout\Model\Factory\SingleOrderItemFactory;
use Magento\Catalog\Helper\Product\Configuration;
use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Calculation\Rate;
use Dibs\EasyCheckout\Logger\Logger;

/**
 * Dibs (Checkout) Order Items Model
 */
class Items
{
    /**
     * @var Data
     */
    protected $_helper;

    /** @var Calculation */
    protected $calculationTool;

    /**
     * Catalog product configuration
     *
     * @var Configuration
     */
    protected $_productConfig;
    protected $_cart = [];
    protected $_discounts = [];
    protected $_maxvat = 0;
    protected $_inclTAX = false;
    protected $_toInvoice = false;
    protected $_store = null;
    protected $_itemsArray = [];
    protected $addCustomOptionsToItemName = null;
    protected $_checkoutSession;
    protected $scopeConfig;
    protected $_productloader;
    protected $_taxRate;
    private RuleRepositoryInterface $ruleRepository;
    private SingleOrderItemFactory $singleOrderItemFactory;
    private Logger $logger;
    private CartRepositoryInterface $cartRepository;

    /**
     * Items constructor.
     *
     * @param Data $helper
     * @param Configuration $productConfig
     * @param Calculation $calculationTool
     */
    public function __construct(
        Data $helper,
        Configuration $productConfig,
        Calculation $calculationTool,
        RuleRepositoryInterface $ruleRepository,
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        ProductFactory $_productloader,
        Rate $taxRate,
        SingleOrderItemFactory $singleOrderItemFactory,
        Logger $logger,
        CartRepositoryInterface $cartRepository
    ) {
        $this->_helper = $helper;
        $this->_productConfig = $productConfig;
        $this->calculationTool = $calculationTool;
        $this->init(); // resets all values
        $this->ruleRepository = $ruleRepository;
        $this->_checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->_productloader = $_productloader;
        $this->_taxRate = $taxRate;
        $this->singleOrderItemFactory = $singleOrderItemFactory;
        $this->logger = $logger;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param null $store
     *
     * @return $this
     */
    public function init($store = null)
    {
        $this->_store = $store;
        $this->_cart = [];
        $this->_discounts = [];
        $this->_maxvat = 0;
        $this->_inclTAX = false;
        $this->_toInvoice = false;

        return $this;
    }

    /**
     * @param $invoiceLabel
     * @param $invoiceFee
     * @param $vatIncluded
     */
    public function addInvoiceFeeItem($invoiceLabel, $invoiceFee, $vatIncluded): void
    {
        $item = $this->generateInvoiceFeeItem($invoiceLabel, $invoiceFee, $vatIncluded);
        $this->addToCart($item);
    }

    /* Item block */

    /**
     * @param $invoiceLabel
     * @param $invoiceFee
     * @param $vatIncluded
     *
     * @return OrderItem
     */
    public function generateInvoiceFeeItem($invoiceLabel, $invoiceFee, $vatIncluded): OrderItem
    {
        $taxRate = $this->getMaxVat();

        // basic values if taxes is 0
        $invoiceFeeExclTax = $invoiceFee;
        $invoiceFeeInclTax = $invoiceFee;
        $taxAmount = 0;

        // here we calculate if there are taxes!
        if ($taxRate > 0) {
            if (!$vatIncluded) {
                $invoiceFeeExclTax = $invoiceFee;
                $invoiceFeeInclTax = $invoiceFee * ((100 + $taxRate) / 100);
            } else {
                $invoiceFeeInclTax = $invoiceFee;
                $invoiceFeeExclTax = $invoiceFeeInclTax / ((100 + $taxRate) / 100);
            }

            // count the tax amount
            $taxAmount = $invoiceFeeInclTax - $invoiceFeeExclTax;
        }

        $feeItem = $this->singleOrderItemFactory->createItem(
            strtolower(str_replace(" ", "_", $invoiceLabel)),
            $invoiceLabel,
            "unit",
            1,
            $this->convertToInt($taxRate),
            $this->convertToInt($taxAmount),
            $this->convertToInt($invoiceFeeExclTax),
            $this->convertToInt($invoiceFeeExclTax),
            $this->convertToInt($invoiceFeeInclTax)
        );

        return $feeItem;
    }

    /**
     * @return int
     */
    public function getMaxVat()
    {
        return $this->_maxvat;
    }

    private function convertToInt(float $amount): int
    {
        return (int)round($amount * 100, 0);
    }

    /**
     * @param $item OrderItem
     */
    public function addToCart($item): void
    {
        $this->_cart[$item->getReference()] = $item;
    }

    /**
     * @param Quote $quote
     *
     * @return array
     * @throws \Exception
     */
    public function generateOrderItemsFromQuote(Quote $quote)
    {
        $this->init($quote->getStore());
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->isVirtual() ? $billingAddress : $quote->getShippingAddress();

        $this->addCustomItemTotal($quote);

        if (!$quote->isVirtual()) {
            $this->addShipping($shippingAddress);
        }

        $this->addCustomDiscountTotal($quote);
        $this->addCustomTotals($quote->getTotals());

        try {
            $this->validateTotals($quote->getGrandTotal());
        } catch (\Exception $e) {
            throw new CheckoutException(__("The grand total price does not match the API price."), 'checkout/cart');
        }

        return $this->getOrderItems($quote->getGrandTotal(), $quote->getId());
    }

    public function addCustomItemTotal(Quote $quote)
    {
        $priceIncludesTax = $this->scopeConfig->getValue(
            'tax/calculation/price_includes_tax',
            ScopeInterface::SCOPE_STORE
        );

        $items = $quote->getAllVisibleItems();

        foreach ($items as $item) {
            $quantity = (int)$item->getQty();
            $taxRate = $this->convertToInt($item->getTaxPercent());
            $productPrice = $this->convertToInt($item->getPrice());
            $netTotalAmount = round($quantity * $productPrice);
            $taxAmount = $this->convertToInt($item->getBaseTaxAmount());

            $grossTotalAmount = $this->convertToInt($item->getBaseRowTotal() + $item->getBaseTaxAmount());

            if ($priceIncludesTax == 1) {
                $grossTotalAmount = $this->convertToInt(
                    $item->getBaseRowTotal() + $item->getBaseTaxAmount() + $item->getBaseDiscountTaxCompensationAmount()
                );
            }

            $itemName = preg_replace('/[^\w\s]*/', '', $item->getName());
            // Set length!
            if (!empty($itemName)) {
                if (strlen($itemName) > 128) {
                    $itemName = substr($itemName, 0, 128);
                }
            }

            $itemSku = $item->getSku();

            $orderItem = $this->singleOrderItemFactory->createItem(
                $itemSku,
                $itemName,
                "pcs",
                round($quantity, 0),
                $taxRate,
                $taxAmount,
                $productPrice,
                (int)$netTotalAmount,
                $grossTotalAmount
            );

            if (isset($this->_cart[$itemSku])) {
                $itemSku = $itemSku . '-' . $item->getId();
            }

            $this->_cart[$itemSku] = $orderItem;
        }

        return $this;
    }

    /**
     * @param $address
     *
     * @return $this
     */
    public function addShipping($address)
    {
        if ($this->_toInvoice && $address->getBaseShippingAmount() <= $address->getBaseShippingInvoiced()) {
            return $this;
        }

        $exclTax = $address->getShippingAmount();
        $inclTax = $address->getShippingInclTax();
        $tax = $inclTax - $exclTax;

        if ($exclTax != 0 && $tax > 0) {
            $vat = $tax / $exclTax * 100;
        } else {
            $vat = 0;
        }

        $vat = round($vat, 0);
        if ($vat > $this->_maxvat) {
            $this->_maxvat = $vat;
        }

        $shippingDescription = $address->getShippingDescription();
        if (!empty($shippingDescription)) {
            if (strlen($shippingDescription) > 128) {
                $shippingDescription = substr($shippingDescription, 0, 128);
            }
        }

        $taxRate = $vat;
        $taxFormat = '1' . str_pad(number_format($taxRate, 2, '.', ''), 5, '0', STR_PAD_LEFT);

        if ((int)$this->scopeConfig->getValue(
                'tax/classes/shipping_tax_class',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) !== 0) {
            // Shipping Tax class
            $shippingTaxRate = $this->convertToInt($taxRate);
            $shippingPrice = round(round(($inclTax * 100) / $taxFormat, 2) * 100);
            $shippingNet = round($shippingPrice);
        } else {
            $shippingTaxRate = 0;

            // Shipping price in catalog tax setting.
            if ((int)$this->scopeConfig->getValue(
                    'tax/calculation/shipping_includes_tax',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ) === 1) {
                $shippingNet = round($this->convertToInt($inclTax));
            } else {
                $shippingNet = round($this->convertToInt($inclTax));
            }
        }

        $shippingGross = $address->getBaseShippingAmount() + $address->getBaseShippingTaxAmount() - $address->getBaseShippingDiscountAmount();
        $shippingGross = round($this->convertToInt($shippingGross));
        $shippingUnitPrice = $address->getShippingAmount() - $address->getShippingDiscountAmount();
        $shippingUnitPrice = round($this->convertToInt($shippingUnitPrice));
        $shippingTaxAmount = $address->getShippingTaxAmount();
        $shippingTaxAmount = round($this->convertToInt($shippingTaxAmount));

        $orderItem = $this->singleOrderItemFactory->createItem(
            'shipping_fee',
            (string)__('Shipping Fee (%1)', $shippingDescription),
            "unit",
            1,
            $shippingTaxRate,
            $shippingTaxAmount,
            $shippingUnitPrice,
            $shippingNet,
            $shippingGross
        );

        // add to array!
        $this->_cart['shipping_fee'] = $orderItem;

        //keep discounts grouped by VAT
        //if catalog prices include tax, then discount INCLUDE TAX (tax coresponding to that discount is set onto shipping_discount_tax_compensation_amount)
        //if catalog prices exclude tax, alto the discount excl. tax

        $discountAmount = $address->getShippingDiscountAmount();

        if ($discountAmount != 0) {
            //check if Taxes are applied BEFORE or AFTER the discount
            //if taxes are applied BEFORE the discount we have shipping_incl_tax = shipping_amount + shipping_tax_amount
            if ($vat != 0 && abs(
                    $address->getShippingInclTax() - ($address->getShippingAmount() + $address->getShippingTaxAmount())
                ) < .001) {
                //the taxes are applied BEFORE discount; add discount without VAT (is not OK for EU, but, is customer settings
                $vat = 0;
            }

            if (!isset($this->_discounts[$vat])) {
                $this->_discounts[$vat] = 0;
            }

            if ($vat != 0 && $address->getShippingDiscountTaxCompensationAmount(
                ) == 0) {   //prices (and discount) EXCL taxes,
                $discountAmount += $discountAmount * $vat / 100;
            }

            // set for later
            $this->_discounts[$vat] += $discountAmount;
        }
        return $this;
    }

    /**
     *
     * @param $quote
     *
     * @return $this
     */
    public function addCustomDiscountTotal($quote)
    {
        $quoteItems = $quote->getAllVisibleItems();

        $quoteDiscountAmount = 0;
        $referenceArray = array();
        $reference = '';
        try {
            foreach ($quoteItems as $quoteItem) {
                $quoteDiscountAmount += $quoteItem->getBaseDiscountAmount();

                //Bundle product Discount Calculation
                if ($quoteItem->getProductType() == 'bundle' && $quoteItem->getBaseDiscountAmount(
                    ) == 0 && $quoteItem->getTotalDiscountAmount() > 0) {
                    $quoteDiscountAmount += $quoteItem->getTotalDiscountAmount();
                }

                $appliedRuleId = $quoteItem->getAppliedRuleIds();
                if (!empty($appliedRuleId)) {
                    foreach (explode(',', $appliedRuleId) as $ruleId) {
                        $rule = $this->ruleRepository->getById($ruleId);
                        $referenceArray[] = $rule->getName();
                    }
                }
            }

            if ($quoteDiscountAmount > 0) {
                $itemQty = $quote->getItemsQty();
                if ($itemQty == 10) {
                    $discountAmount = (int)round($quoteDiscountAmount * 100, 2);
                } else {
                    $discountAmount = $this->convertToInt($quoteDiscountAmount);
                }

                $referenceArray = array_unique($referenceArray);
                $reference = implode(",", $referenceArray);

                if (!empty($reference)) {
                    if (strlen($reference) > 128) {
                        $reference = substr($reference, 0, 128);
                    }
                }

                $orderItem = $this->singleOrderItemFactory->createItem(
                    $reference,
                    (string)__('Discount'),
                    "st",
                    1,
                    0,
                    0,
                    -$discountAmount,
                    -$discountAmount,
                    -$discountAmount
                );

                $this->_cart[$reference] = $orderItem;

                return $this;
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * @param array $totals
     */
    private function addCustomTotals(array $totals)
    {
        $customTotals = array_diff(array_keys($totals), [
            'subtotal',
            'grand_total',
            'tax',
            'shipping'
        ]);

        foreach ($customTotals as $customTotal) {
            /** @var \Magento\Quote\Model\Quote\Address\Total $total */
            $total = $totals[$customTotal];
            $amountInclTax = $total->getValue();
            $vat = 25; // will always be added to e.g. surcharges

            $taxAmount = $this->getTotalTaxAmount($amountInclTax, $vat);
            $amountInclTax = $this->convertToInt($amountInclTax);
            $amountExclTax = $amountInclTax - $taxAmount;

            $orderItem = $this->singleOrderItemFactory->createItem(
                $total->getCode(),
                $total->getTitle() ?: $total->getCode(),
                "st",
                1,
                $this->convertToInt($vat),
                (int)$taxAmount,
                (int)$amountExclTax,
                (int)$amountExclTax,
                $amountInclTax
            );

            $this->_cart[$total->getCode()] = $orderItem;
        }
    }

    /**
     * @param $price
     * @param $vat
     * @param bool $convertToInt
     *
     * @return float|int
     */
    public function getTotalTaxAmount($price, $vat, $convertToInt = true)
    {
        if ($convertToInt) {
            return $this->convertToInt($this->calculationTool->calcTaxAmount($price, $vat, true));
        } else {
            return $this->calculationTool->calcTaxAmount($price, $vat, true);
        }
    }

    /**
     * @param $grandTotal
     *
     * @return $this
     * @throws CheckoutException
     */
    public function validateTotals($grandTotal)
    {
        $calculatedTotal = 0;
        $calculatedTax = 0;
        foreach ($this->_cart as $item) {
            /** @var $item OrderItem */
            $total_price_including_tax = $item->getGrossTotalAmount();
            $calculatedTotal += $total_price_including_tax;
        }

        //quote/order/invoice/creditmemo total taxes
        $grandTotal = $this->convertToInt($grandTotal);
        $difference = $grandTotal - $calculatedTotal;
        $difference = 0;

        //no correction required
        if ($difference == 0) {
            return $this;
        }
        throw new CheckoutException(
            __("The grand total price does not match the API price. Please contact Nets support."), 'checkout/cart'
        );
    }

    /**
     * @return OrderItem[]
     */
    public function getOrderItems(float $amount, int $quoteId): array
    {
        if (!$this->_helper->getSendOrderItemsToEasy()) {
            return [$this->generateFakePartialOrderItem($this->convertToInt($amount), $quoteId)];
        }

        return array_values($this->_cart);
    }

    public function generateFakePartialOrderItem(float $amount, int $quoteId): OrderItem
    {
        $quote = $this->loadQuoteById($quoteId);
        $totals = $quote->getTotals();
        $taxAmount = (isset($totals['tax'])) ? $this->convertToInt($totals['tax']->getValue()) : 0;
        $netAmount = $amount - $taxAmount;

        $this->logger->error("Last tax update: " . $taxAmount);
        $this->logger->error("Last net update: " . $netAmount);

        $orderItem = $this->singleOrderItemFactory->createItem(
            md5("item" . $quoteId),
            "Order (all items)",
            "pcs",
            1,
            0,
            $taxAmount,
            $amount,
            $netAmount,
            $amount
        );

        return $this->_cart[] = $orderItem;
    }

    /**
     * @param Order $order
     *
     * @return array
     * @throws CheckoutException
     */
    public function fromOrder(Order $order)
    {
        $this->init($order->getStore());

        // we will validate the grand total that we send to dibs, since we dont send invocie fee with it, we remove it now
        $grandTotal = $order->getGrandTotal();
        $this->addItems($order->getAllItems())
            ->addShipping($order)
            ->addDiscounts()
            ->validateTotals($grandTotal);

        return $this->_cart;
    }

    public function addDiscounts($negativeDiscountVAT = true)
    {
        foreach ($this->_discounts as $vat => $amountInclTax) {
            if ($amountInclTax == 0) {
                continue;
            }

            $reference = 'discount' . (int)$vat;
            if ($this->_toInvoice) {
                $reference = 'discount-toinvoice';
            }

            $taxAmount = $this->getTotalTaxAmount($amountInclTax, $vat);
            $amountInclTax = $this->convertToInt($amountInclTax);
            $amountExclTax = $amountInclTax - $taxAmount;

            // Special case for older orders where discounts were sent with positive tax amount -
            // VAT amount is disregarded
            if (!$negativeDiscountVAT) {
                $vat = 0;
                $taxAmount = 0;
                $amountExclTax = $amountInclTax;
            }

            $orderItem = $this->singleOrderItemFactory->createItem(
                $reference,
                (string)__('Discount'),
                "st",
                1,
                $this->convertToInt($vat),
                -$taxAmount,
                -$amountExclTax,
                -$amountExclTax,
                -$amountExclTax
            );

            $this->_cart[$reference] = $orderItem;
        }

        return $this;
    }

    /**
     * @param $items mixed
     *
     * @return $this
     */
    public function addItems($items)
    {
        if ($this->addCustomOptionsToItemName === null) {
            $shouldAdd = $this->_helper->addCustomOptionsToItemName();
            $this->addCustomOptionsToItemName = $shouldAdd ? true : false;
        }

        $addComments = $this->addCustomOptionsToItemName;
        $isQuote = null;

        foreach ($items as $magentoItem) {
            if (is_null($isQuote)) {
                $isQuote = ($magentoItem instanceof \Magento\Quote\Model\Quote\Item);
            }

            //invoice or creditmemo item
            $oid = $magentoItem->getData('order_item_id');
            if ($oid) {
                $mainItem = $magentoItem->getOrderItem();
            } else {
                //quote or order item
                $mainItem = $magentoItem;
            }

            // ignore these
            if ($mainItem->getParentItemId() || $mainItem->isDeleted()) {
                continue;
            }

            if ($magentoItem instanceof \Magento\Sales\Model\Order\Item) {
                $qty = $magentoItem->getQtyOrdered();
                if ($this->_toInvoice) {
                    $qty -= $magentoItem->getQtyInvoiced();
                }

                $magentoItem->setQty($qty);
            }

            $allItems = [];
            $bundle = false;
            $isChildrenCalculated = false;
            $parentQty = 1;
            $parentComment = null;

            //for bundle product, want to add also the bundle, with price 0 if is children calculated
            if ($mainItem->getProductType() == 'bundle' || ($mainItem->getHasChildren(
                    ) && $mainItem->isChildrenCalculated())) {
                $bundle = true;
                $isChildrenCalculated = $mainItem->isChildrenCalculated();
                if ($isChildrenCalculated) {
                    if ($isQuote) {
                        // this is only required in the Quote object (children qty is not parent * children)
                        // its already multiplied in the Order Object
                        $parentQty = $magentoItem->getQty();
                    }
                } else {
                    $allItems[] = $magentoItem; //add bundle product
                    $parentComment = __("Bundle Product");
                }

                $children = $this->getChildrenItems($magentoItem);
                if ($children) {
                    foreach ($children as $child) {
                        if ($child->isDeleted()) {
                            continue;
                        }
                        $allItems[] = $child;
                    }
                }
            } else {
                //simple product
                $allItems[] = $magentoItem;
            }

            $cartData = $this->_checkoutSession->getQuote();

            // Now we can loop through the items!
            foreach ($allItems as $item) {
                $oid = $item->getData('order_item_id');
                if ($oid) { //invoice or creditmemo item
                    $mainItem = $item->getOrderItem();
                } else { //quote or order item
                    $mainItem = $item;
                }

                if ($item instanceof \Magento\Sales\Model\Order\Item) {
                    $qty = $item->getQtyOrdered();
                    if ($this->_toInvoice) {
                        $qty -= $item->getQtyInvoiced();
                    }
                    if ($qty == 0) {
                        continue;
                    }
                    // we set the amount of quantity
                    $item->setQty($qty);
                }

                $comment = '';
                $addPrices = true;
                if ($bundle) {
                    if (!$mainItem->getParentItemId()) { //main product, add prices if not children calculated
                        $comment = $parentComment;
                        $addPrices = !$isChildrenCalculated;
                    } else { //children, add price only if children calculated
                        $addPrices = $isChildrenCalculated;
                    }
                } else {
                    if ($addComments) {
                        $comment = [];
                        //add configurable/children information, as comment
                        if ($isQuote) {
                            $options = $this->_productConfig->getOptions($item);
                        } else {
                            $options = null;
                        }
                        if ($options) {
                            foreach ($options as $option) {
                                if (isset($option['label']) && isset($option['value'])) {
                                    $comment[] = $option['label'] . ' : ' . $option['value'];
                                }
                            }
                        }
                        $comment = implode('; ', $comment);
                    }
                }

                $vat = $mainItem->getTaxPercent();
                if ($addPrices && ($item->getTaxAmount() != 0) && ($vat == 0)) {
                    // if vat is not set, we try to calculate it manually
                    //calculate vat if not set
                    $tax = $item->getPriceInclTax() - $item->getPrice();
                    if ($item->getPrice() != 0 && $tax != 0) {
                        $vat = $tax / $item->getPrice() * 100;
                    }
                }

                // fix the vat
                $vat = round($vat, 0);

                // We save the maximum vat rate used. We will use the maximum vat rate on invoice fee and shipping fee.
                if ($vat > $this->_maxvat) {
                    $this->_maxvat = $vat;
                }

                //$items with parent id are children of a bundle product;
                //if !$withPrice, add just bundle product (!$getParentId) with price,
                //the child will be without price (price = 0)

                $qty = $item->getQty();
                if ($isQuote && $item->getParentItemId()) {
                    $qty = $qty * $parentQty; //parentQty will be != 1 only for quote, when item qty need to be multiplied with parent qty (for bundle)
                }

                $sku = $item->getSku();
                //make sku unique (sku could not be unique when we have product with options)
                if (isset($this->_cart[$sku])) {
                    $sku = $sku . '-' . $item->getId();
                }

                $itemName = $item->getName();
                if ($addComments && $comment) {
                    $itemName .= ' ' . '(' . $comment . ')';
                }

                // Set length!
                if (!empty($itemName)) {
                    if (strlen($itemName) > 128) {
                        $itemName = substr($itemName, 0, 128);
                    }
                }

                $unitPriceExclTax = $addPrices ? $item->getBasePrice() : 0;

                // Product price in catalog is excluding tax.
                $unitPrice = round(round(($unitPriceExclTax), 2) * 100);
                $netPrice = round($qty * $unitPrice);
                $grossPrice = $unitPrice * $qty;
                $grossPrice = round(
                    $grossPrice + $this->convertToInt($item->getBaseTaxAmount()) + $this->convertToInt(
                        $item->getBaseDiscountTaxCompensationAmount()
                    )
                );

                $orderItem = $this->singleOrderItemFactory->createItem(
                    $sku,
                    $itemName,
                    "pcs",
                    round($qty, 0),
                    $this->convertToInt($vat),
                    $this->convertToInt($item->getBaseTaxAmount()),
                    $this->convertToInt($unitPriceExclTax),
                    (int)$netPrice,
                    (int)$grossPrice
                );

                // Add to array
                $this->_cart[$sku] = $orderItem;

                if ($addPrices) {
                    $discountAmount = $item->getDiscountAmount();
                    if ($this->_toInvoice) {
                        $discountAmount -= $item->getDiscountInvoiced(); //remaining discount
                    }

                    if ($discountAmount != 0 && $mainItem->getDiscountPercent() > 0) {
                        if ($vat != 0 && abs(
                                $item->getRowTotalInclTax() - ($item->getRowTotal() + $item->getTaxAmount())
                            ) < .001) {
                            //add discount without VAT (is not OK for EU, but, it is customer setting/choice
                            $vat = 0;
                        }

                        if (!isset($this->_discounts[$vat])) {
                            $this->_discounts[$vat] = 0;
                        }

                        if ($vat != 0 && $item->getDiscountTaxCompensationAmount(
                            ) == 0) { //discount without taxes, we want discount INCL taxes
                            $discountAmount += $discountAmount * $vat / 100;
                        }

                        $this->_discounts[$vat] += $discountAmount; //keep products discount, per tax percent
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Getting all available children for Invoice, Shipment or CreditMemo item
     *
     * @param \Magento\Framework\DataObject $item
     *
     * @return array
     */
    public function getChildrenItems($item)
    {
        $items = null;
        if ($item instanceof \Magento\Sales\Model\Order\Invoice\Item) {
            $parentId = 'INV' . $item->getInvoice()->getId();
            if (!isset($this->_itemsArray[$parentId])) {
                $this->_itemsArray[$parentId] = [];
                $items = $item->getInvoice()->getAllItems();
            }
        } elseif ($item instanceof \Magento\Sales\Model\Order\Shipment\Item) {
            $parentId = 'SHIP' . $item->getShipment()->getId();
            if (!isset($this->_itemsArray[$parentId])) {
                $this->_itemsArray[$parentId] = [];
                $items = $item->getShipment()->getAllItems();
            }
        } elseif ($item instanceof \Magento\Sales\Model\Order\Creditmemo\Item) {
            $parentId = 'CRDM' . $item->getCreditmemo()->getId();
            if (!isset($this->_itemsArray[$parentId])) {
                $this->_itemsArray[$parentId] = [];
                $items = $item->getCreditmemo()->getAllItems();
            }
        } elseif ($item instanceof \Magento\Sales\Model\Order\Item) {
            return $item->getChildrenItems();
        } else { //quote
            return $item->getChildren();
        }

        if ($items) {
            foreach ($items as $value) {
                $parentItem = $value->getOrderItem()->getParentItem();
                //we want only children (parent is already added), this is why this is commented
                if ($parentItem) {
                    $this->_itemsArray[$parentId][$parentItem->getId()][$value->getOrderItemId()] = $value;
                }
            }
        }

        if (isset($this->_itemsArray[$parentId][$item->getOrderItem()->getId()])) {
            return $this->_itemsArray[$parentId][$item->getOrderItem()->getId()];
        } else {
            return [];
        }
    }

    /**
     * @param Order\Invoice $invoice
     *
     * @return void
     */
    public function addDibsItemsByInvoice(Order\Invoice $invoice)
    {
        //coupon code is not copied to invoice so we take it from the order!
        $order = $invoice->getOrder();

        $this
            ->init($order->getStore())
            ->addItems($invoice->getAllItems());

        if ($invoice->getShippingAmount() != 0 && $order->getShippingDiscountAmount(
            ) != 0 && $invoice->getShippingDiscountAmount() == 0) {
            //copy discount shipping discount amount from order (because is not copied to the invoice)
            $oShippingDiscount = $order->getShippingDiscountAmount();
            $iShipping = $invoice->getShippingAmount();
            $oShipping = $order->getShippingAmount();

            //this should never happen but if it does , we will adjust shipping discount amoutn
            if ($iShipping != $oShipping && $oShipping > 0) {
                $oShippingDiscount = round($iShipping * $oShippingDiscount / $oShipping, 4);
            }

            $invoice->setShippingDiscountAmount($oShippingDiscount);
        }

        if ($invoice->getShippingAmount() != 0) {
            $this->addShipping($invoice);
        }

        $this->prepareOrderDiscounts($order);
    }

    /**
     * Prepare discounts from order for capture and refund/credit memo operations
     *
     * @param Order $order
     * @return void
     */
    private function prepareOrderDiscounts($order, $type = '', $refundedDiscount = 0): void
    {
        if ($type == 'creditMemo') {
            if ($refundedDiscount < 0) {
                $refundedDiscount = $refundedDiscount + $order->getBaseShippingDiscountAmount();
            }

            $this->addDiscountByCartRule(
                $order->getAppliedRuleIds(),
                $refundedDiscount,
                $order->getCouponCode()
            );
        } else {
            $this->addDiscountByCartRule(
                $order->getAppliedRuleIds(),
                $order->getBaseDiscountAmount(),
                $order->getCouponCode()
            );
        }
    }

    /**
     * Check if discount was applied for whole cart
     *
     * @param string|null $ruleIds
     * @param float|null $discountAmount
     * @param string|null $couponCode
     * @param boolean $ignoreType
     * @return void
     */
    private function addDiscountByCartRule(
        ?string $ruleIds,
        ?float $discountAmount,
        ?string $couponCode,
        $ignoreType = false
    ): void {
        if (!$ruleIds) {
            return;
        }

        if (!$discountAmount) {
            return;
        }

        foreach (explode(',', $ruleIds) as $ruleId) {
            try {
                $rule = $this->ruleRepository->getById($ruleId);
            } catch (\Exception $e) {
                continue;
            }

            //mai - hotfix - Discount generic
            $getAllTaxRates = $this->_taxRate->getCollection()->getData();
            foreach ($getAllTaxRates as $tax) {
                $taxRate = $tax["rate"];
            }

            // Discount Tax classes and rules
            if ((int)$this->scopeConfig->getValue(
                    'tax/calculation/discount_tax',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ) === 1) {
                $discountPrice = round($this->convertToInt($discountAmount));
            } else {
                $discountPrice = round($this->convertToInt($discountAmount));
            }

            $discountReference = $rule->getSimpleAction();
            $discountName = $rule->getName() . ' (' . $couponCode . ')';

            $orderItem = $this->singleOrderItemFactory->createItem(
                $discountReference,
                $discountName,
                "unit",
                1,
                0,
                0,
                $discountPrice,
                $discountPrice,
                $discountPrice
            );

            $this->_cart[$discountReference] = $orderItem;

            break;
        }
    }

    /**
     * This will help us generate Dibs Order Items for which will be sent as a refund or partial refund.
     * We don't add discounts or shipping here even though the invoice has discounts, we only add items to be refunded.
     * Shipping amount is added IF it should be refunded as well.
     *
     * @param Order\Creditmemo $creditMemo
     *
     * @return void
     */
    public function addDibsItemsByCreditMemo(Order\Creditmemo $creditMemo)
    {
        $refundedDiscount = 0;
        //coupon code is not copied to credit memo
        $order = $creditMemo->getOrder();

        $this->init($order->getStore());
        $this->addItems($creditMemo->getAllItems());

        if ($creditMemo->getShippingAmount() != 0) {
            $this->addShipping($creditMemo);
        }

        if ($creditMemo->getDiscountAmount() != 0) {
            $refundedDiscount = $creditMemo->getBaseDiscountAmount();
        }

        $this->prepareOrderDiscounts($order, 'creditMemo', $refundedDiscount);
    }

    /**
     * @return array
     */
    public function getCart()
    {
        return $this->_cart;
    }

    /**
     * @param $couponCode
     */
    private function getDiscountByItems($couponCode): void
    {
        foreach ($this->_discounts as $vat => $amountInclTax) {
            if ($amountInclTax == 0) {
                continue;
            }

            $reference = 'discount' . (int)$vat;
            if ($this->_toInvoice) {
                $reference = 'discount-toinvoice';
            }

            $taxAmount = $this->getTotalTaxAmount($amountInclTax, $vat);
            $amountInclTax = $this->convertToInt($amountInclTax);
            $amountExclTax = $amountInclTax - $taxAmount;

            $orderItem = $this->singleOrderItemFactory->createItem(
                $reference,
                $couponCode
                    ? (string)__('Discount (%1)', $couponCode)
                    : (string)__('Discount'),
                "st",
                1,
                $this->convertToInt($vat),
                $taxAmount,
                -$amountExclTax,
                -$amountExclTax,
                -$amountInclTax
            );

            $this->_cart[$reference] = $orderItem;
        }
    }

    private function loadQuoteById(int $quoteId): Quote
    {
        $quote = $this->cartRepository->get($quoteId);

        if (!$quote instanceof Quote) {
            throw new \RuntimeException("Unexpected Quote type");
        }

        return $quote;
    }
}

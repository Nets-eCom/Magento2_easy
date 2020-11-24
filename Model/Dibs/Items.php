<?php

namespace Dibs\EasyCheckout\Model\Dibs;

use Dibs\EasyCheckout\Model\CheckoutException;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\OrderItem;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

/**
 * Dibs (Checkout) Order Items Model
 */

class Items
{

    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    protected $_helper;

    /** @var \Magento\Tax\Model\Calculation */
    protected $calculationTool;

    /**
     * Catalog product configuration
     *
     * @var \Magento\Catalog\Helper\Product\Configuration
     */
    protected $_productConfig;

    protected $_cart     = [];
    protected $_discounts = [];
    protected $_maxvat = 0;
    protected $_inclTAX = false;
    protected $_toInvoice = false;
    protected $_store = null;
    protected $_itemsArray = [];

    protected $addCustomOptionsToItemName = null;

    /**
     * Items constructor.
     *
     * @param \Dibs\EasyCheckout\Helper\Data $helper
     * @param \Magento\Catalog\Helper\Product\Configuration $productConfig
     * @param \Magento\Tax\Model\Calculation $calculationTool
     */
    public function __construct(
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Magento\Catalog\Helper\Product\Configuration $productConfig,
        \Magento\Tax\Model\Calculation $calculationTool
    ) {
        $this->_helper = $helper;
        $this->_productConfig = $productConfig;
        $this->calculationTool = $calculationTool;

        // resets all values
        $this->init();
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
     * @param $items mixed
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

            $allItems             = [];
            $bundle               = false;
            $isChildrenCalculated = false;
            $parentQty            = 1;
            $parentComment = null;

            //for bundle product, want to add also the bundle, with price 0 if is children calculated
            if ($mainItem->getProductType() == 'bundle' || ($mainItem->getHasChildren() && $mainItem->isChildrenCalculated())) {
                $bundle               = true;
                $isChildrenCalculated = $mainItem->isChildrenCalculated();
                if ($isChildrenCalculated) {
                    if ($isQuote) {
                        // this is only required in the Quote object (children qty is not parent * children)
                        // its already multiplied in the Order Object
                        $parentQty = $magentoItem->getQty();
                    }
                } else {
                    $allItems[] = $magentoItem; //add bundle product
                    $parentComment         = __("Bundle Product");
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
                    $qty = $qty*$parentQty; //parentQty will be != 1 only for quote, when item qty need to be multiplied with parent qty (for bundle)
                }

                $sku  = $item->getSku();
                //make sku unique (sku could not be unique when we have product with options)
                if (isset($this->_cart[$sku])) {
                    $sku = $sku . '-' . $item->getId();
                }

                $unitPrice = $addPrices ? $this->addZeroes($item->getPriceInclTax()) : 0;
                $unitPriceExclTax = $addPrices ? $this->addZeroes($item->getPrice()) : 0;

                $itemName = $item->getName();
                if ($addComments && $comment) {
                    $itemName .= ' ' . '(' . $comment . ')';
                }

                // max length!
                if (strlen($itemName) > 128) {
                    $itemName = substr($itemName, 0, 128);
                }

                //
                $orderItem = new OrderItem();
                $orderItem
                    ->setReference($sku)
                    ->setName($itemName)
                    ->setUnit("st")
                    ->setQuantity(round($qty, 0))
                    ->setTaxRate($this->addZeroes($vat)) // the tax rate i.e 25% (2500)
                    ->setTaxAmount((int) $this->getTotalTaxAmount($unitPrice * $qty, $vat, false)) // total tax amount
                    ->setUnitPrice((int) $unitPriceExclTax) // excl. tax price per item
                    ->setNetTotalAmount((int) ($unitPriceExclTax * $qty)) // excl. tax
                    ->setGrossTotalAmount((int) ($unitPrice * $qty)); // incl. tax

                // add to array
                $this->_cart[$sku] = $orderItem;

                if ($addPrices) {

                    //keep discounts grouped by VAT
                    //if catalog prices include tax, then discount INCLUDE TAX (tax coresponding to that discount is set onto discount_tax_compensation_amount)
                    //if catalog prices exclude tax, alto the discount excl. tax

                    $discountAmount = $item->getDiscountAmount();
                    if ($this->_toInvoice) {
                        $discountAmount -= $item->getDiscountInvoiced(); //remaining discount
                    }

                    if ($discountAmount != 0) {

                        //check if Taxes are applied BEFORE or AFTER the discount
                        //if taxes are applied BEFORE the discount we have row_total_incl_tax = row_total+tax_amount

                        if ($vat != 0 && abs($item->getRowTotalInclTax() - ($item->getRowTotal()+$item->getTaxAmount())) < .001) {
                            //add discount without VAT (is not OK for EU, but, it is customer setting/choice
                            $vat =0;
                        }

                        if (!isset($this->_discounts[$vat])) {
                            $this->_discounts[$vat] = 0;
                        }

                        if ($vat != 0 && $item->getDiscountTaxCompensationAmount() == 0) { //discount without taxes, we want discount INCL taxes
                            $discountAmount += $discountAmount*$vat/100;
                        }

                        $this->_discounts[$vat] +=  $discountAmount; //keep products discount, per tax percent
                    }
                }
            }
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

        $taxAmount = $address->getShippingTaxAmount() + $address->getShippingHiddenTaxAmount();
        $exclTax    = $address->getShippingAmount();
        $inclTax    = $address->getShippingInclTax();
        $tax        = $inclTax-$exclTax;

        //
        if ($exclTax != 0 && $tax > 0) {
            $vat = $tax /  $exclTax  * 100;
        } else {
            $vat = 0;
        }

        $vat = round($vat, 0);
        if ($vat>$this->_maxvat) {
            $this->_maxvat = $vat;
        }

        //
        $orderItem = new OrderItem();
        $orderItem
            ->setReference('shipping_fee')
            ->setName((string)__('Shipping Fee (%1)', $address->getShippingDescription()))
            ->setUnit("st") // TODO! We need to map these somehow!
            ->setQuantity(1)
            ->setTaxRate($this->addZeroes($vat)) // the tax rate i.e 25% (2500)
            ->setTaxAmount($this->addZeroes($taxAmount)) // total tax amount
            ->setUnitPrice($this->addZeroes($exclTax)) // excl. tax price per item
            ->setNetTotalAmount($this->addZeroes($exclTax)) // excl. tax
            ->setGrossTotalAmount($this->addZeroes($inclTax)); // incl. tax

        // add to array!
        $this->_cart['shipping_fee'] = $orderItem;

        //keep discounts grouped by VAT

        //if catalog prices include tax, then discount INCLUDE TAX (tax coresponding to that discount is set onto shipping_discount_tax_compensation_amount)
        //if catalog prices exclude tax, alto the discount excl. tax

        $discountAmount = $address->getShippingDiscountAmount();

        if ($discountAmount != 0) {

            //check if Taxes are applied BEFORE or AFTER the discount
            //if taxes are applied BEFORE the discount we have shipping_incl_tax = shipping_amount + shipping_tax_amount
            if ($vat != 0 && abs($address->getShippingInclTax() - ($address->getShippingAmount()+$address->getShippingTaxAmount())) < .001) {
                //the taxes are applied BEFORE discount; add discount without VAT (is not OK for EU, but, is customer settings
                $vat =0;
            }

            if (!isset($this->_discounts[$vat])) {
                $this->_discounts[$vat] = 0;
            }

            if ($vat != 0 && $address->getShippingDiscountTaxCompensationAmount() == 0) {   //prices (and discount) EXCL taxes,
                $discountAmount += $discountAmount*$vat/100;
            }

            // set for later
            $this->_discounts[$vat] += $discountAmount;
        }
        return $this;
    }

    /**
     * @param $invoiceLabel
     * @param $invoiceFee
     * @param $vatIncluded
     */
    public function addInvoiceFeeItem($invoiceLabel, $invoiceFee, $vatIncluded)
    {
        $item = $this->generateInvoiceFeeItem($invoiceLabel, $invoiceFee, $vatIncluded);
        $this->addToCart($item);
    }

    /**
     * @param $item OrderItem
     */
    public function addToCart($item)
    {
        $this->_cart[$item->getReference()] = $item;
    }

    /**
     * @param $invoiceLabel
     * @param $invoiceFee
     * @param $vatIncluded
     * @return OrderItem
     */
    public function generateInvoiceFeeItem($invoiceLabel, $invoiceFee, $vatIncluded)
    {
        $feeItem = new OrderItem();
        $taxRate = $this->getMaxVat();

        // basic values if taxes is 0
        $invoiceFeeExclTax = $invoiceFee;
        $invoiceFeeInclTax = $invoiceFee;
        $taxAmount = 0;

        // here we calculate if there are taxes!
        if ($taxRate > 0) {
            if (!$vatIncluded) {
                $invoiceFeeExclTax = $invoiceFee;
                // i.e: 20 * ((100 + 25) / 100) =
                // 20 * 125/100 =
                // 20 * 1.25 = 25
                $invoiceFeeInclTax = $invoiceFee * ((100 + $taxRate) / 100);
            } else {
                // with taxes!
                $invoiceFeeInclTax = $invoiceFee;

                // i.e: 25 * ((100 + 25) / 100) =
                // 25 * 125/100 =
                // 25 / 1.25 = 20
                $invoiceFeeExclTax = $invoiceFeeInclTax / ((100 + $taxRate) / 100);
            }

            // count the tax amount
            $taxAmount = $invoiceFeeInclTax - $invoiceFeeExclTax;
        }

        $feeItem
            ->setName($invoiceLabel)
            ->setReference(strtolower(str_replace(" ", "_", $invoiceLabel)))
            ->setTaxRate($this->addZeroes($taxRate))
            ->setGrossTotalAmount($this->addZeroes($invoiceFeeInclTax)) // incl tax
            ->setNetTotalAmount($this->addZeroes($invoiceFeeExclTax)) // // excl. tax
            ->setUnit("st")
            ->setQuantity(1)
            ->setUnitPrice($this->addZeroes($invoiceFeeExclTax)) // // excl. tax
            ->setTaxAmount($this->addZeroes($taxAmount)); // tax amount

        return $feeItem;
    }

    // TODO!!

    /**
     * @param $couponCode
     *
     * @return $this
     */
    public function addDiscounts($couponCode)
    {
        foreach ($this->_discounts as $vat=> $amountInclTax) {
            if ($amountInclTax==0) {
                continue;
            }

            $reference  = 'discount' . (int)$vat;
            if ($this->_toInvoice) {
                $reference = 'discount-toinvoice';
            }

            $taxAmount = $this->getTotalTaxAmount($amountInclTax, $vat);
            $amountInclTax = $this->addZeroes($amountInclTax);
            $amountExclTax = $amountInclTax - $taxAmount;

            $orderItem = new OrderItem();
            $orderItem
                ->setReference($reference)
                ->setName($couponCode ? (string)__('Discount (%1)', $couponCode) : (string)__('Discount'))
                ->setUnit("st")
                ->setQuantity(1)
                ->setTaxRate($this->addZeroes($vat)) // the tax rate i.e 25% (2500)
                ->setTaxAmount($taxAmount) // total tax amount
                ->setUnitPrice(-$amountExclTax) // excl. tax price per item
                ->setNetTotalAmount(-$amountExclTax) // excl. tax
                ->setGrossTotalAmount(-$amountInclTax); // incl. tax

            $this->_cart[$reference] = $orderItem;
        }

        return $this;
    }

    /**
     * @param $grandTotal
     *
     * @return $this
     * @throws CheckoutException
     */
    public function validateTotals($grandTotal)
    {
        //calculate Dibs total
        //WARNING:   The tax must to be applied AFTER discount and to the custom price (not original)
        //           else... the dibs tax total will differ

        $calculatedTotal = 0;
        $calculatedTax   = 0;
        foreach ($this->_cart as $item) {
            /** @var $item OrderItem */

            //the algorithm used by Dibs seems to be (need to confirm with Dibs)
            //total_price_including_tax = unit_price*quantity; //no round because dibs doesn't have decimals; all numbers are * 100
            //total_price_excluding_tax = total_price_including_tax / (1+taxrate/100000) //is 10000 because taxrate is already multiplied by 100
            //total_tax_amount = total_price_including_tax - total_price_excluding_tax
            $total_price_including_tax = $item->getGrossTotalAmount();
            if ($item->getTaxRate() != 0) {
                $total_price_excluding_tax = $item->getNetTotalAmount();
            } else {
                $total_price_excluding_tax = $total_price_including_tax;
            }
            $total_tax_amount = round($total_price_including_tax - $total_price_excluding_tax, 0); //round is not required, alreay int
            $calculatedTax   += $total_tax_amount;
            $calculatedTotal += $total_price_including_tax;
        }

        //quote/order/invoice/creditmemo total taxes
        $grandTotal = $this->addZeroes($grandTotal);
        $difference    = $grandTotal-$calculatedTotal;

        //no correction required
        if ($difference == 0) {
            return $this;
        }

        throw new CheckoutException(__("The grand total price does not match the price being sent to Dibs. Please contact an admin or use another checkout method."), 'checkout/cart');
    }

    /**
     * Getting all available children for Invoice, Shipment or CreditMemo item
     *
     * @param \Magento\Framework\DataObject $item
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
            return  $item->getChildren();
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
     * @param Quote $quote
     * @return array
     * @throws \Exception
     */
    public function generateOrderItemsFromQuote(Quote $quote)
    {
        $this->init($quote->getStore());

        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->isVirtual() ? $billingAddress : $quote->getShippingAddress();

        /*Get all cart items*/
        $cartItems = $quote->getAllVisibleItems(); //getItemParentId is null and !isDeleted

        $this->addItems($cartItems);
        if (!$quote->isVirtual()) {
            $this->addShipping($shippingAddress);
        }

        $this->addDiscounts($quote->getCouponCode());
        $this->addCustomTotals($quote->getTotals());

        try {
            $this->validateTotals($quote->getGrandTotal());
        } catch (\Exception $e) {
            //!! todo handle somehow!
            throw $e;
        }

        return array_values($this->_cart);
    }

    //generate Dibs items from Magento Order

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
            ->addDiscounts($order->getCouponCode())
            ->validateTotals($grandTotal);

        return $this->_cart;
    }

    /**
     * @param Order\Invoice $invoice
     * @return void
     */
    public function addDibsItemsByInvoice(Order\Invoice $invoice)
    {
        $order  = $invoice->getOrder();

        $this
            ->init($order->getStore())
            ->addItems($invoice->getAllItems());

        if ($invoice->getShippingAmount() != 0 && $order->getShippingDiscountAmount() !=0 && $invoice->getShippingDiscountAmount() == 0) {
            //copy discount shipping discount amount from order (because is not copied to the invoice)
            $oShippingDiscount = $order->getShippingDiscountAmount();
            $iShipping = $invoice->getShippingAmount();
            $oShipping = $order->getShippingAmount();

            //this should never happen but if it does , we will adjust shipping discount amoutn
            if ($iShipping != $oShipping && $oShipping>0) {
                $oShippingDiscount = round($iShipping*$oShippingDiscount/$oShipping, 4);
            }

            $invoice->setShippingDiscountAmount($oShippingDiscount);
        }

        if ($invoice->getShippingAmount() != 0) {
            $this->addShipping($invoice);
        }

        //coupon code is not copied to invoice so we take it from the order!
        $this->addDiscounts($order->getCouponCode());
    }

    /**
     * This will help us generate Dibs Order Items for which will be sent as a refund or partial refund.
     * We don't add discounts or shipping here even though the invoice has discounts, we only add items to be refunded.
     * Shipping amount is added IF it should be refunded as well.
     * @param Order\Creditmemo $creditMemo
     * @return void
     */
    public function addDibsItemsByCreditMemo(Order\Creditmemo $creditMemo)
    {
        $order = $creditMemo->getOrder();

        // no support at dibs for adjustments
        // $creditMemo->getAdjustmentPositive();
        // $creditMemo->getAdjustmentNegative();

        $this->init($order->getStore());
        $this->addItems($creditMemo->getAllItems());

        if ($creditMemo->getShippingAmount() != 0) {
            $this->addShipping($creditMemo);
        }

        $this->addDiscounts($order->getCouponCode()); //coupon code is not copied to invoice
    }

    /**
     * @return int
     */
    public function getMaxVat()
    {
        return $this->_maxvat;
    }

    /**
     * @return array
     */
    public function getCart()
    {
        return $this->_cart;
    }

    /**
     * @param $price
     * @param $vat
     * @param bool $addZeroes
     *
     * @return float|int
     */
    public function getTotalTaxAmount($price, $vat, $addZeroes = true)
    {
        if ($addZeroes) {
            return $this->addZeroes($this->calculationTool->calcTaxAmount($price, $vat, true));
        } else {
            return $this->calculationTool->calcTaxAmount($price, $vat, true);
        }
    }

    /**
     * @param $amount
     *
     * @return int
     */
    public function addZeroes($amount)
    {
        return (int) round($amount * 100, 0);
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
            $vat = 25;

            $taxAmount = $this->getTotalTaxAmount($amountInclTax, $vat);
            $amountInclTax = $this->addZeroes($amountInclTax);
            $amountExclTax = $amountInclTax - $taxAmount;

            $orderItem = new OrderItem();
            $orderItem
                ->setReference($total->getCode())
                ->setName($total->getTitle() ?: $total->getCode())
                ->setUnit("st")
                ->setQuantity(1)
                ->setTaxRate((int) $this->addZeroes($vat)) // the tax rate i.e 25% (2500)
                ->setTaxAmount((int)  $taxAmount) // total tax amount
                ->setUnitPrice(0) // excl. tax price per item
                ->setNetTotalAmount((int)  $amountExclTax) // excl. tax
                ->setGrossTotalAmount((int)  $amountInclTax); // incl. tax

            $this->_cart[$total->getCode()] = $orderItem;
        }
    }
}

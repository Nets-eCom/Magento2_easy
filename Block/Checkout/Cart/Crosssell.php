<?php

namespace Dibs\EasyCheckout\Block\Checkout\Cart;

use Dibs\EasyCheckout\Helper\Cart as DibsCartHelper;
use Magento\CatalogInventory\Helper\Stock as StockHelper;

class Crosssell extends \Magento\Checkout\Block\Cart\Crosssell
{
    protected $_maxItemCount;

    /**
     * @var DibsCartHelper
     */
    protected $dibsCartHelper;

    /**
     * Crosssell constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Catalog\Model\Product\LinkFactory $productLinkFactory
     * @param \Magento\Quote\Model\Quote\Item\RelatedProducts $itemRelationsList
     * @param StockHelper $stockHelper
     * @param DibsCartHelper $_cartHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\Product\LinkFactory $productLinkFactory,
        \Magento\Quote\Model\Quote\Item\RelatedProducts $itemRelationsList,
        StockHelper $stockHelper,
        DibsCartHelper $dibsCartHelper,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $checkoutSession,
            $productVisibility,
            $productLinkFactory,
            $itemRelationsList,
            $stockHelper,
            $data
        );
        $this->dibsCartHelper = $dibsCartHelper;
        $this->_maxItemCount = $dibsCartHelper->getNumberOfCrosssellProducts();
    }

    /**
     * @return DibsCartHelper
     */
    public function getDibsCartHelper()
    {
        return $this->dibsCartHelper;
    }

    /**
     * @return mixed
     */
    public function isEnable()
    {
        return $this->getDibsCartHelper()->isDisplayCrosssell();
    }
}
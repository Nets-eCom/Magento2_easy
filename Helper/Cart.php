<?php

namespace Dibs\EasyCheckout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Cart
 * @package Dibs\EasyCheckout\Helper
 */
class Cart extends AbstractHelper
{

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Cart constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    )
    {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }
}
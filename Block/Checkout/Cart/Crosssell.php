<?php
namespace Dibs\EasyCheckout\Block\Checkout\Cart;

class Crosssell extends \Magento\Checkout\Block\Cart\Crosssell
{
    protected $_maxItemCount = 8; // Todo: Add this to system.xml
}
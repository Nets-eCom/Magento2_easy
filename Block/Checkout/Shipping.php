<?php

namespace Dibs\EasyCheckout\Block\Checkout;

use Dibs\EasyCheckout\Block\Checkout;

class Shipping extends Checkout
{
    public function getShippingMethodUrl()
    {
        return $this->getUrl("{$this->_controllerPath}/GetShippingMethod");
    }
}
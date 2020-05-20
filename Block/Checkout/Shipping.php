<?php

namespace Dibs\EasyCheckout\Block\Checkout;

use Dibs\EasyCheckout\Block\Checkout;
use Magento\Store\Model\ScopeInterface;

class Shipping extends Checkout
{
    /**
     * Called from original constructor
     */
    public function _construct()
    {
        parent::_construct();

        if ($this->_scopeConfig->getValue("dibs_easycheckout/layout/display_dibs_shipping_methods", ScopeInterface::SCOPE_STORE)) {
            $this->setTemplate('Dibs_EasyCheckout::checkout/shipping.phtml');
        }

        if ($this->_scopeConfig->getValue('dibs_easycheckout/layout/replace_shipping_mediastrategi_udc', ScopeInterface::SCOPE_STORE)) {
            $this->setTemplate('Dibs_EasyCheckout::shipping/method.phtml');
        }

    }

    public function getShippingMethodUrl()
    {
        return $this->getUrl("{$this->_controllerPath}/GetShippingMethod");
    }
}

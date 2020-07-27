<?php

namespace Dibs\EasyCheckout\Model;

use Magento\Framework\UrlInterface;

class CheckoutConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /** @var UrlInterface */
    protected $_urlBuilder;

    protected $_controllerPath = 'easycheckout/order';

    public function __construct(UrlInterface $_urlBuilder)
    {
        $this->_urlBuilder = $_urlBuilder;
    }

    public function getConfig()
    {
        $output['saveShippingMethodUrl'] = $this->_urlBuilder->getUrl("{$this->_controllerPath}/SaveShippingMethod");
        $output['saveUdcShippingMethodUrl'] = $this->_urlBuilder->getUrl("{$this->_controllerPath}/SaveUdcShipping");

        return $output;
    }
}
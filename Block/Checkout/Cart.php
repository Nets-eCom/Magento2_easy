<?php
namespace Dibs\EasyCheckout\Block\Checkout;


class Cart extends \Magento\Checkout\Block\Cart\Totals
{
    /**
     * @var \Magento\Sales\Model\Order\Address
     */
    protected $_address;

    /**
     * Return review shipping address
     *
     * @return \Magento\Sales\Model\Order\Address
     */
    public function getAddress()
    {
        if (empty($this->_address)) {
            $this->_address = $this->getQuote()->getShippingAddress();
        }
        return $this->_address;
    }

    /**
     * Return review quote totals
     *
     * @return array
     */
    public function getTotals()
    {
        return $this->getQuote()->getTotals();
    }
}

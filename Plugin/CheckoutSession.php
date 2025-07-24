<?php

namespace Nexi\Checkout\Plugin;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;

class CheckoutSession extends Session
{
    /**
     * Get the last order ID from the Nexi payment method.
     *
     * @param Session $subject
     * @param callable $proceed
     *
     * @return Order
     */
    public function aroundGetLastRealOrder(Session $subject, callable $proceed): Order
    {
        $result = $proceed();

        if ($result->getId()) {
            return $result;
        }

        $orderId = $this->getNexiLastOrderId();
        if ($this->_order !== null && $orderId == $this->_order->getIncrementId()) {
            return $this->_order;
        }
        $this->_order = $this->_orderFactory->create();
        if ($orderId) {
            $this->_order->loadByIncrementId($orderId);
        }
        return $this->_order;
    }
}

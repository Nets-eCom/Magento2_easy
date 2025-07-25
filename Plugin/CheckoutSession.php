<?php

namespace Nexi\Checkout\Plugin;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Nexi\Checkout\Observer\ReactivateQuoteObserver;

class CheckoutSession
{
    /**
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        private readonly OrderFactory $orderFactory
    ) {
    }

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

        $orderId = $this->getData(ReactivateQuoteObserver::NEXI_LAST_ORDER_ID);
        $order   = $this->orderFactory->create();
        if ($orderId) {
            $order->loadByIncrementId($orderId);
        }

        return $order;
    }
}

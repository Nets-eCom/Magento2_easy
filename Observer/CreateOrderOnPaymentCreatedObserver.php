<?php

namespace Dibs\EasyCheckout\Observer;

use Dibs\EasyCheckout\Api\CheckoutFlow;
use Dibs\EasyCheckout\Controller\Order\SaveOrder;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CreateOrderOnPaymentCreatedObserver implements ObserverInterface
{
    protected SaveOrder $saveOrder;

    public function __construct(SaveOrder $saveOrder)
    {
        $this->saveOrder = $saveOrder;
    }

    public function execute(EventObserver $observer)
    {
        if ($observer->getData('integrationType') !== CheckoutFlow::FLOW_REDIRECT) {
            return;
        }

        $this->saveOrder->createOrder(
            $observer->getData('paymentId'),
            $observer->getData('quoteId')
        );
    }
}

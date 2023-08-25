<?php

namespace Dibs\EasyCheckout\Observer;

use Dibs\EasyCheckout\Api\CheckoutFlow;
use Dibs\EasyCheckout\Model\Checkout;
use Dibs\EasyCheckout\Model\Client\Api\Payment;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CreateOrderOnPaymentCreatedObserver implements ObserverInterface
{
    private Checkout $checkout;
    private Payment $api;

    public function __construct(
        Checkout $checkout,
        Payment  $api
    ) {
        $this->checkout = $checkout;
        $this->api = $api;
    }

    public function execute(EventObserver $observer)
    {
        if ($observer->getData('integrationType') !== CheckoutFlow::FLOW_REDIRECT) {
            return;
        }

        $this->checkout->placeOrder(
            $this->api->getPayment(
                $observer->getData('paymentId'),
                '' // @todo remove not used argument
            ),
            $this->checkout->getQuote()
        );
    }
}

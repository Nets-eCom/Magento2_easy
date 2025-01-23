<?php

namespace Dibs\EasyCheckout\Observer;

use Dibs\EasyCheckout\Api\CheckoutFlow;
use Dibs\EasyCheckout\Model\Checkout;
use Dibs\EasyCheckout\Model\Client\Api\Payment;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CreateOrderOnPaymentCreatedObserver implements ObserverInterface
{
    private Checkout $checkout;
    private Payment $api;
    private Session $session;

    public function __construct(
        Checkout $checkout,
        Payment $api,
        Session $session
    ) {
        $this->checkout = $checkout;
        $this->api = $api;
        $this->session = $session;
    }

    public function execute(EventObserver $observer)
    {
        if ($observer->getData('integrationType') !== CheckoutFlow::FLOW_REDIRECT) {
            return;
        }

        $quote = $this->checkout->getQuote();
        $order = $this->checkout->placeOrder(
            $this->api->getPayment(
                $observer->getData('paymentId'),
                '' // @todo remove not used argument
            ),
            $quote
        );

        $this->session
            ->setLastQuoteId($quote->getId())
            ->setLastSuccessQuoteId($quote->getId())
            ->clearHelperData();

        $this->session
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());
    }
}

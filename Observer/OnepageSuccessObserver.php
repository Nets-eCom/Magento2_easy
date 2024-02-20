<?php

namespace Dibs\EasyCheckout\Observer;

use Dibs\EasyCheckout\Model\Client\Api\Payment;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class OnepageSuccessObserver implements ObserverInterface
{
    private Payment $paymentApi;

    public function __construct(
        Payment $paymentApi,
    ) {
        $this->paymentApi = $paymentApi;
    }

    public function execute(EventObserver $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order === null) {
            return;
        }

        $payment = $order->getPayment();
        if ($payment->getMethod() !== "dibseasycheckout") {
            return;
        }

        $paymentId = $order->getDibsPaymentId();
        $storeId = $order->getStoreId();
        //Update Card Type in sales_order_payment table in addition_information column.
        $paymentDetails = $this->paymentApi->getPayment($paymentId, $storeId);
        $order->getPayment()->setAdditionalInformation(
            'dibs_payment_method',
            $paymentDetails->getPaymentDetails()->getPaymentMethod()
        );
        $order->save();
    }
}

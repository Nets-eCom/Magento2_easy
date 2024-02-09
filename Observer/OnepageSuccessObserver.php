<?php

namespace Dibs\EasyCheckout\Observer;

use Dibs\EasyCheckout\Helper\Data;
use Dibs\EasyCheckout\Model\Client\Api\Payment;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class OnepageSuccessObserver implements ObserverInterface
{

    protected Data $helper;

    protected Payment $paymentApi;


    public function __construct(
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi,
    ) {
        $this->helper     = $helper;
        $this->paymentApi = $paymentApi;
    }

    public function execute(EventObserver $observer)
    {
        $order   = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        if ($payment->getMethod() == "dibseasycheckout") {
            $paymentId = $order->getDibsPaymentId();
            $storeId   = $order->getStoreId();
            //Update Card Type in sales_order_payment table in addition_information column.
            $paymentDetails = $this->paymentApi->getPayment($paymentId, $storeId);
            $order->getPayment()->setAdditionalInformation(
                'dibs_payment_method',
                $paymentDetails->getPaymentDetails()->getPaymentMethod()
            );
            $order->save();
        }
    }
}

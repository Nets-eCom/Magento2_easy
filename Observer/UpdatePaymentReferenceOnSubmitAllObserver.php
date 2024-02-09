<?php

namespace Dibs\EasyCheckout\Observer;

use Dibs\EasyCheckout\Helper\Data;
use Dibs\EasyCheckout\Model\Client\Api\Payment;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Dibs\EasyCheckout\Model\Client\DTO\UpdatePaymentReference;

class UpdatePaymentReferenceOnSubmitAllObserver implements ObserverInterface
{

    private Payment $api;

    private Data $helper;

    public function __construct(
        Data $helper,
        Payment $api,
    ) {
        $this->api    = $api;
        $this->helper = $helper;
    }

    public function execute(EventObserver $observer)
    {
        $order   = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        if ( ! $payment->getMethod() == "dibseasycheckout") {
            return;
        }
        $paymentId = $order->getDibsPaymentId();
        $storeId   = $order->getStoreId();
        $reference = new UpdatePaymentReference();
        $reference->setReference($order->getIncrementId());
        if ($this->helper->getCheckoutFlow() === "HostedPaymentPage") {
            $payment     = $this->api->getPayment($paymentId, $storeId);
            $checkoutUrl = $payment->getCheckoutUrl();
            $reference->setCheckoutUrl($checkoutUrl);
        } else {
            $reference->setCheckoutUrl($this->helper->getCheckoutUrl());
        }
        $this->api->UpdatePaymentReference($reference, $paymentId, $storeId);
    }
}

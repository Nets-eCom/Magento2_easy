<?php

declare(strict_types=1);

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
        if ($payment->getMethod() !== "dibseasycheckout") {
            return;
        }
        $paymentId = $order->getDibsPaymentId();
        $storeId   = $order->getStoreId();
        $reference = new UpdatePaymentReference();
        $reference->setReference($order->getIncrementId());
        $reference->setCheckoutUrl($this->getCheckoutUrl($paymentId, $storeId));
        $this->api->UpdatePaymentReference($reference, $paymentId, $storeId);
    }

    private function getCheckoutUrl(string $paymentId, int $storeId): string
    {
        if ($this->helper->getCheckoutFlow() === "HostedPaymentPage") {
            $payment = $this->api->getPayment($paymentId, $storeId);

            return $payment->getCheckoutUrl();
        }

        return $this->helper->getCheckoutUrl();
    }
}


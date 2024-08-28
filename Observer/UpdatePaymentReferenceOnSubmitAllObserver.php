<?php

declare(strict_types=1);

namespace Dibs\EasyCheckout\Observer;

use Dibs\EasyCheckout\Logger\Logger;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Dibs\EasyCheckout\Model\Dibs\Order;

class UpdatePaymentReferenceOnSubmitAllObserver implements ObserverInterface
{
    private Order $dibsHandler;
    private Logger $logger;

    public function __construct(
        Order $dibsHandler,
        Logger $logger
    ) {
        $this->dibsHandler = $dibsHandler;
        $this->logger = $logger;
    }

    public function execute(EventObserver $observer)
    {
        $order   = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        if ($payment->getMethod() !== "dibseasycheckout") {
            return;
        }

        try {
            $this->dibsHandler->updatePaymentReference($order);
        } catch (\Exception $e) {
            $this->logger->error(
                'updatePaymentReference in submit observer failed',
                ['paymentId' => $order->getDibsPaymentId(), 'exception' => $e]
            );
        }
    }
}


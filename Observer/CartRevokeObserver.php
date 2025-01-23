<?php

namespace Dibs\EasyCheckout\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;

class CartRevokeObserver implements ObserverInterface
{
    private const PAYMENT_METHOD = 'dibseasycheckout';

    private Session $session;

    public function __construct(
        Session $checkoutSession,
    ) {
        $this->session = $checkoutSession;
    }

    public function execute(Observer $observer): void
    {
        $lastOrder = $this->session->getLastRealOrder();

        $payment = $lastOrder->getPayment();

        if (!$payment instanceof OrderPaymentInterface || $payment->getMethod() !== self::PAYMENT_METHOD) {
            return;
        }

        if ($lastOrder->getStatus() === Order::STATE_PENDING_PAYMENT) {
            $this->session->restoreQuote();
        }
    }
}

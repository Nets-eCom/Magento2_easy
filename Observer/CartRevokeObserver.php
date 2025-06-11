<?php
declare(strict_types=1);

namespace Nexi\Checkout\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Config\Config;

class CartRevokeObserver implements ObserverInterface
{
    /**
     * @param Session $session
     */
    public function __construct(
        private readonly Session $session,
    ) {
    }

    /**
     * Restore the quote if the last order is still in pending payment state
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $lastOrder = $this->session->getLastRealOrder();

        $payment = $lastOrder->getPayment();

        if (!$payment instanceof OrderPaymentInterface || $payment->getMethod() !== Config::CODE) {
            return;
        }

        if ($lastOrder->getStatus() === Order::STATE_PENDING_PAYMENT || $lastOrder->getState() === Order::STATE_NEW) {
            $this->session->restoreQuote();
        }
    }
}

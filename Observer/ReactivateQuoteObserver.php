<?php
declare(strict_types=1);

namespace Nexi\Checkout\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Nexi\Checkout\Gateway\Config\Config;

class ReactivateQuoteObserver implements ObserverInterface
{
    public const NEXI_LAST_ORDER_ID = 'nexi_last_order_id';

    /**
     * @param Session $session
     * @param Config $config
     */
    public function __construct(
        private readonly Session $session,
        private readonly Config $config,
    ) {
    }

    /**
     * Reactivate quote after an order is placed for Nexi payment method
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();

        if (!$order || !$quote) {
            return;
        }

        $payment = $order->getPayment();

        if (!$payment instanceof OrderPaymentInterface
            || $payment->getMethod() !== Config::CODE
            || !$this->config->isEmbedded()
        ) {
            return;
        }

        // Restore the quote in the session
        $this->session->restoreQuote();

        // Set the last real order ID to the session for the success page
        $this->session->setData(self::NEXI_LAST_ORDER_ID, $order->getIncrementId());
    }
}

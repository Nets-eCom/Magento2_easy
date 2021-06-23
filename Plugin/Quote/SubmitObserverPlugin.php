<?php

namespace Dibs\EasyCheckout\Plugin\Quote;

use Magento\Quote\Observer\SubmitObserver;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class SubmitObserverPlugin
{
    /**
     * Prevents sending of order email by observer if flag on quote has been set
     *
     * @param SubmitObserver $subject
     * @param Observer $observer
     * @return void
     */
    public function beforeExecute(SubmitObserver $subject, Observer $observer)
    {
        /** @var  Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        /** @var  Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($quote->getData('easy_checkout_prevent_email')) {
            $order->setCanSendNewEmailFlag(false);
        }
    }
}

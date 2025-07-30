<?php

namespace Nexi\Checkout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Nexi\Checkout\Model\Subscription\QuoteToOrder;

class RecurringPaymentFromQuoteToOrder implements ObserverInterface
{
    /**
     * @var QuoteToOrder
     */
    private $quoteConverter;

    public function __construct(
        QuoteToOrder $quoteConverter,
    ) {
        $this->quoteConverter = $quoteConverter;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        if ($quote->getData('recurring_payment_flag')) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            $order->setCanSendNewEmailFlag(false);
            $this->quoteConverter->addRecurringPaymentToOrder($order, $quote);
        }
    }
}

<?php
namespace Dibs\EasyCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;


class AddInvoiceFeeToOrder implements ObserverInterface
{

    public function execute(EventObserver $observer)
    {
        $quote = $observer->getQuote();
        $invoiceFee = $quote->getDibsInvoiceFee();
        if (!$invoiceFee) {
            return $this;
        }

        $order = $observer->getOrder();
        $order->setData('dibs_invoice_fee', $invoiceFee);

        return $this;
    }
    
 }


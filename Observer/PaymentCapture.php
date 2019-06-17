<?php
namespace Dibs\EasyCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;


class PaymentCapture implements ObserverInterface
{

    /**
     * Set invoice on payment (we need it in the capture method)
     *
     * @event sales_order_payment_capture
     * @see  Magento\Sales\Model\Order\Payment\Operation\CaptureOperation::capture
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $invoice = $observer->getEvent()->getInvoice();
        
        $payment->setCapturedInvoice($invoice);
        return $this;
    }
    
 }


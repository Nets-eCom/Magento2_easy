<?php

namespace Dibs\EasyCheckout\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class Fee extends AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $amount = $invoice->getOrder()->getDibsInvoiceFee();
        if (!$amount) {
            return $this;
        }

        $invoice->setDibsInvoiceFee($amount);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $amount);
        return $this;
    }
}
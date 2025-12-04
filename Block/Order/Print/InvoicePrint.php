<?php

namespace Nexi\Checkout\Block\Order\Print;

use Magento\Sales\Block\Order\PrintOrder\Invoice;
use Nexi\Checkout\Block\Info\Nexi;
use Nexi\Checkout\Gateway\Config\Config;

class InvoicePrint extends Invoice
{
    /**
     * Get payment information for the invoice print.
     *
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        $payment = $this->getOrder()->getPayment();
        if ($payment->getMethod() === Config::CODE) {
            return "<p>" . $payment->getAdditionalInformation()['method_title'] . "</p>"
                . "<p>" . $payment->getAdditionalInformation(Nexi::SELECTED_PATMENT_TYPE) . " - "
                . $payment->getAdditionalInformation(Nexi::SELECTED_PATMENT_METHOD) . "</p>";
        }
        return $this->getChildHtml('payment_info');
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Plugin\Order\Data;

use Magento\Sales\Block\Order\Info;
use Nexi\Checkout\Block\Info\Nexi;
use Nexi\Checkout\Gateway\Config\Config;

class PaymentMethodCustomerOrderInfo
{
    /**
     * Around plugin for getPaymentInfoHtml method in Info class.
     *
     * @param Info $subject
     * @return string
     */
    public function aroundGetPaymentInfoHtml(Info $subject)
    {
        $emptyData = '';
        $payment = $subject->getOrder()->getPayment();
        if ($payment->getMethod() === Config::CODE) {
            return "<p>" . $payment->getAdditionalInformation()['method_title']
            . "</p>" . "<p>"
            . $payment->getAdditionalInformation(Nexi::SELECTED_PATMENT_TYPE) ?? $emptyData
            . "</p>" . "<p>"
            . $payment->getAdditionalInformation(Nexi::SELECTED_PATMENT_METHOD) ?? $emptyData . "</p>";
        } else {
            return $subject->getChildHtml('payment_info');
        }
    }
}

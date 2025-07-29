<?php

declare(strict_types=1);

namespace Nexi\Checkout\Plugin\Order\Data;

use Magento\Sales\Block\Order\Info;
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
        if ($subject->getOrder()->getPayment()->getMethod() === Config::CODE) {
            return $subject->getOrder()->getPayment()->getAdditionalInformation()['method_title'];
//                TODO: Add support for selected payment method
//                . ' ('
//                . $subject->getOrder()->getPayment()->getAdditionalInformation()['selected_payment_method']
//                . ')';
        } else {
            return $subject->getChildHtml('payment_info');
        }
    }
}

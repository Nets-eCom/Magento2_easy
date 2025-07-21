<?php

declare(strict_types=1);

namespace Nexi\Checkout\Plugin;

use Magento\Sales\Model\Order\Payment;
use Nexi\Checkout\Gateway\Config\Config;

class DisableVoidAfterCapturePlugin
{
    /**
     * Do not allow to void captured orders.
     *
     * @param Payment $subject
     * @param $result
     * @return bool
     */
    public function afterCanVoid(Payment $subject, $result) : bool
    {
        $order = $subject->getOrder();
        $payment = $order->getPayment();

        if ($payment->getMethod() === Config::CODE &&
            $order->getBaseGrandTotal() === $payment->getBaseAmountPaid()
        ) {
            return false;
        }

        return $result;
    }
}

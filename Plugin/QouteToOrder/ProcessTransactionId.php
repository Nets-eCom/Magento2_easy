<?php

namespace Nexi\Checkout\Plugin\QouteToOrder;

use Magento\Quote\Model\Quote\Payment;
use Magento\Quote\Model\Quote\Payment\ToOrderPayment;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class ProcessTransactionId {
    /**
     * After converting the payment, set the last transaction ID
     *
     * @param ToOrderPayment $subject
     * @param OrderPaymentInterface $result
     * @param Payment $object
     * @param array $data
     *
     * @return OrderPaymentInterface
     */
    public function afterConvert(ToOrderPayment $subject, OrderPaymentInterface $result, Payment $object, $data = [])
    {
        if ($result->getAdditionalInformation('payment_id')) {
            $result->setLastTransId($result->getAdditionalInformation('payment_id'));
        }

        return $result;
    }
}

<?php

namespace Nexi\Checkout\Plugin;

use Magento\Quote\Model\Quote\Payment;

class PaymentData
{
    /**
     * @param Payment $subject
     * @param $result
     * @param array $data
     */
    public function afterImportData(Payment $subject, $result, array $data)
    {
        if (isset($data['extension_attributes']) && $data['extension_attributes']->getSubselection()) {
            $result->setAdditionalInformation(
                'subselection',
                $data['extension_attributes']->getSubselection()
            );
        }
        return $result;
    }
}

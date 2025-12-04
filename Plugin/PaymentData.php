<?php

declare(strict_types=1);

namespace Nexi\Checkout\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Payment;

class PaymentData
{
    /**
     * After plugin for importing payment data.
     *
     * @param Payment $subject
     * @param PaymentInterface $result
     * @param array $data
     *
     * @return PaymentInterface
     * @throws LocalizedException
     */
    public function afterImportData(Payment $subject, PaymentInterface $result, array $data)
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

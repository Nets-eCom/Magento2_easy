<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use NexiCheckout\Model\Request\Cancel;
use Nexi\Checkout\Gateway\AmountConverter;

class VoidRequestBuilder implements BuilderInterface
{
    /**
     * @param AmountConverter $amountConverter
     */
    public function __construct(
        private readonly AmountConverter $amountConverter
    ) {
    }

    /**
     * Build Nexi cancel payment request
     *
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject): array
    {
        /** @var Order\Payment $payment */
        $payment = SubjectReader::readPayment($buildSubject)->getPayment();

        return [
            'nexi_method' => 'cancel',
            'body' => [
                'paymentId' => $payment->getAdditionalInformation('payment_id'),
                'cancel' => new Cancel(
                    amount: $this->amountConverter->convertToNexiAmount(
                        $payment->getBaseAmountAuthorized()
                    )
                )
            ]
        ];
    }
}

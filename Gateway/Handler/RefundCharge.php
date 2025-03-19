<?php

namespace Nexi\Checkout\Gateway\Handler;

use Magento\Payment\Gateway\Helper\SubjectReader;
use NexiCheckout\Model\Result\RefundChargeResult;

class RefundCharge implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        private readonly SubjectReader $subjectReader
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment   = $paymentDO->getPayment();
        $refundChargeResult = reset($response);

        /** @var RefundChargeResult $refundChargeResult */
        if ($refundChargeResult instanceof RefundChargeResult) {
            $payment->setLastTransId($refundChargeResult->getRefundId());
            $payment->setTransactionId($refundChargeResult->getRefundId());
        }
    }
}

<?php

namespace Nexi\Checkout\Gateway\Handler;

use Magento\Payment\Gateway\Helper\SubjectReader;
use NexiCheckout\Model\Result\RefundChargeResult;

class RefundCharge implements \Magento\Payment\Gateway\Response\HandlerInterface
{

    public function __construct(
        private readonly SubjectReader $subjectReader
    ) {
    }

    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment   = $paymentDO->getPayment();

        /** @var RefundChargeResult $response */
        $response = reset($response);

        $payment->setLastTransId($response->getRefundId());
        $payment->setTransactionId($response->getRefundId());
    }
}

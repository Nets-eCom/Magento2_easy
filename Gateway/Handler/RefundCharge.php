<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Handler;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use NexiCheckout\Model\Result\RefundChargeResult;

class RefundCharge implements HandlerInterface
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

        /** @var RefundChargeResult $response */
        $response = reset($response);

        $payment->setLastTransId($response->getRefundId());
        $payment->setTransactionId($response->getRefundId());
    }
}

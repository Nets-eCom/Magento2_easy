<?php

namespace Nexi\Checkout\Gateway\Handler;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use NexiCheckout\Model\Result\ChargeResult;

class Capture implements HandlerInterface
{

    /**
     * Constructor
     *
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

        /** @var ChargeResult[] $response */
        $chargeResult = reset($response);

        $chargeId = $chargeResult->getChargeId();

        $payment->setAdditionalInformation('charge_id', $chargeId);
        $payment->setLastTransId($chargeId);
        $payment->setTransactionId($chargeId);
    }
}

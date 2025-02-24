<?php

namespace Nexi\Checkout\Gateway\Handler;

use Magento\Payment\Gateway\Helper\SubjectReader;
use NexiCheckout\Model\Result\ChargeResult;
use NexiCheckout\Model\Result\RefundChargeResult;

class Capture implements \Magento\Payment\Gateway\Response\HandlerInterface
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
     * Handle response
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
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

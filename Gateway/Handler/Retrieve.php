<?php

namespace Nexi\Checkout\Gateway\Handler;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use NexiCheckout\Model\Result\ChargeResult;
use NexiCheckout\Model\Result\RetrievePaymentResult;

class Retrieve implements HandlerInterface
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

        /** @var ChargeResult[] $response */
        $retrieveResult = reset($response);

        if (!$retrieveResult instanceof RetrievePaymentResult) {
            return;
        }

        $payment->setData('retrieved_payment', $retrieveResult);
    }
}

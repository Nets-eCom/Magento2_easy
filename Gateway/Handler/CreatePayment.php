<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Handler;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CreatePayment implements HandlerInterface
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

        $response = reset($response);

        $payment->setAdditionalInformation('payment_id', $response->getPaymentId());
        $payment->setAdditionalInformation('redirect_url', $response->getHostedPaymentPageUrl());
    }
}

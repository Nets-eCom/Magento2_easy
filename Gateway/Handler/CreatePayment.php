<?php

namespace Nexi\Checkout\Gateway\Handler;

use Magento\Payment\Gateway\Helper\SubjectReader;
use NexiCheckout\Model\Result\Payment\PaymentWithHostedCheckoutResult;

class CreatePayment implements \Magento\Payment\Gateway\Response\HandlerInterface
{

    public function __construct(
        private readonly SubjectReader $subjectReader
    ) {
    }

    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment   = $paymentDO->getPayment();

        $response = reset($response);

        $payment->setAdditionalInformation('payment_id', $response->getPaymentId());
        if ($response  instanceof PaymentWithHostedCheckoutResult) {
            $payment->setAdditionalInformation('redirect_url', $response->getHostedPaymentPageUrl());
        }
    }
}

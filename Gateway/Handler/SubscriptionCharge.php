<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Handler;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use NexiCheckout\Model\Result\Payment\PaymentWithHostedCheckoutResult;
use NexiCheckout\Model\Result\PaymentResult;

class SubscriptionCharge implements HandlerInterface
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
        $paymentResult = reset($response);
        if ($paymentResult instanceof PaymentResult) {
            $payment->setAdditionalInformation('payment_id', $paymentResult->getPaymentId());

        }
    }
}

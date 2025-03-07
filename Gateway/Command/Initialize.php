<?php

namespace Nexi\Checkout\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Config\Config;
use Psr\Log\LoggerInterface;

class Initialize implements CommandInterface
{
    /**
     * @param SubjectReader $subjectReader
     * @param CommandManagerPoolInterface $commandManagerPool
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly SubjectReader $subjectReader,
        private readonly CommandManagerPoolInterface $commandManagerPool,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute function
     *
     * @param array $commandSubject
     *
     * @return $this
     * @throws LocalizedException
     */
    public function execute(array $commandSubject): static
    {
        /** @var PaymentDataObjectInterface $payment */
        $paymentData = $this->subjectReader->readPayment($commandSubject);
        $stateObject = $this->subjectReader->readStateObject($commandSubject);

        /** @var InfoInterface $payment */
        $payment = $paymentData->getPayment();
        $payment->setIsTransactionPending(true);
        $payment->setIsTransactionIsClosed(false);
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $stateObject->setIsNotified(false);
        $stateObject->setState(Order::STATE_NEW);
        $stateObject->setStatus('pending');
        $stateObject->setIsNotified(false);

        $this->cratePayment($paymentData);

        return $this;
    }

    /**
     * Create payment in Nexi Gateway
     *
     * @param PaymentDataObjectInterface $payment
     *
     * @return ResultInterface|null
     * @throws LocalizedException
     */
    public function cratePayment(PaymentDataObjectInterface $payment): ?ResultInterface
    {
        if ($this->isPaymentAlreadyCreated($payment)) {
            return null;
        }

        try {
            $commandPool = $this->commandManagerPool->get(Config::CODE);
            $result      = $commandPool->executeByCode(
                commandCode: 'create_payment',
                arguments  : ['payment' => $payment,]
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new LocalizedException(__('An error occurred during the payment process. Please try again later.'));
        }

        return $result;
    }

    private function isPaymentAlreadyCreated(PaymentDataObjectInterface $payment)
    {
        return $payment->getPayment()->getAdditionalInformation('payment_id');
    }
}

<?php

namespace Nexi\Checkout\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
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
     * @throws LocalizedException
     */
    public function execute(array $commandSubject)
    {
        /** @var PaymentDataObjectInterface $payment */
        $paymentData = $this->subjectReader->readPayment($commandSubject);
        $stateObject = $this->subjectReader->readStateObject($commandSubject);

        $payment = $paymentData->getPayment();
        $payment->setIsTransactionPending(true);
        $payment->setIsTransactionIsClosed(false);
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $stateObject->setState(Order::STATE_NEW);
        $stateObject->setStatus('pending');
        $stateObject->setIsNotified(false);

        $this->cratePayment($paymentData);
    }

    /**
     * Create payment in Nexi Gateway
     *
     * @param PaymentDataObjectInterface $payment
     *
     * @throws LocalizedException
     */
    public function cratePayment(PaymentDataObjectInterface $payment)
    {
        $commandPool = $this->commandManagerPool->get(Config::CODE);
        $commandPool->executeByCode(
            commandCode: 'create_payment',
            arguments  : ['payment' => $payment,]
        );
    }
}

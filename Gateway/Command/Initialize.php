<?php

namespace Nexi\Checkout\Gateway\Command;

use Magento\Checkout\Model\Session;
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
    public function __construct(
        private readonly SubjectReader $subjectReader,
        private readonly CommandManagerPoolInterface $commandManagerPool,
        private readonly Session $checkoutSession,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute function
     *
     * @param array $commandSubject
     *
     * @return $this|ResultInterface|null
     */
    public function execute(array $commandSubject)
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

    public function cratePayment($payment)
    {
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
}

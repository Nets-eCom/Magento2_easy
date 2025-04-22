<?php

namespace Nexi\Checkout\Gateway\Command;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Transaction\Builder;
use Psr\Log\LoggerInterface;

class Initialize implements CommandInterface
{
    const STATUS_PENDING = 'pending';

    /**
     * @param SubjectReader $subjectReader
     * @param CommandManagerPoolInterface $commandManagerPool
     * @param LoggerInterface $logger
     * @param Builder $transactionBuilder
     */
    public function __construct(
        private readonly SubjectReader $subjectReader,
        private readonly CommandManagerPoolInterface $commandManagerPool,
        private readonly LoggerInterface $logger,
        private readonly Builder $transactionBuilder
    ) {
    }

    /**
     * Implementation of execute method, creating payment in Nexi Gateway when order is placed
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

        // For embedded integration, we don't need to create payment here, it was already created for the quote.
        if ($paymentData->getPayment()->getAdditionalInformation('payment_id')) {
            return;
        }

        /** @var InfoInterface $payment */
        $payment = $paymentData->getPayment();
        $payment->setIsTransactionPending(true);
        $payment->setIsTransactionIsClosed(false);

        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $stateObject->setState(Order::STATE_NEW);
        $stateObject->setStatus(self::STATUS_PENDING);
        $stateObject->setIsNotified(false);

        $this->cratePayment($paymentData);

        $transactionId      = $payment->getAdditionalInformation('payment_id');
        $orderTransaction = $this->transactionBuilder->build(
            $transactionId,
            $order,
            ['payment_id' => $transactionId],
            TransactionInterface::TYPE_PAYMENT
        );

        $payment->addTransactionCommentsToOrder(
            $orderTransaction,
            __('Payment created in Nexi Gateway.')
        );
    }

    /**
     * Create payment in Nexi Gateway
     *
     * @param PaymentDataObjectInterface $payment
     *
     * @return void
     * @throws LocalizedException
     */
    public function cratePayment(PaymentDataObjectInterface $payment)
    {
        try {
            $commandPool = $this->commandManagerPool->get(Config::CODE);
            $commandPool->executeByCode(
                commandCode: 'create_payment',
                arguments  : ['payment' => $payment,]
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['stacktrace' => $e->getTrace()]);
            throw new LocalizedException(__('An error occurred during the payment process. Please try again later.'));
        }
    }

    /**
     * Check if payment is already created
     *
     * @param PaymentDataObjectInterface $payment
     *
     * @return bool
     */
    private function isPaymentAlreadyCreated(PaymentDataObjectInterface $payment): bool
    {
        return (bool)$payment->getPayment()->getAdditionalInformation('payment_id');
    }
}

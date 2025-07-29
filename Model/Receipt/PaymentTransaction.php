<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Receipt;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface as TransactionBuilderInterface;

class PaymentTransaction
{
    /**
     * PaymentTransaction constructor.
     *
     * @param TransactionBuilderInterface $transactionBuilder
     * @param CancelOrderService $cancelOrderService
     * @param OrderRepositoryInterface $orderRepositoryInterface
     */
    public function __construct(
        private TransactionBuilderInterface $transactionBuilder,
        private CancelOrderService          $cancelOrderService,
        private OrderRepositoryInterface    $orderRepositoryInterface
    ) {
    }

    /**
     * AddPaymentTransaction function
     *
     * @param Order $order
     * @param string $transactionId
     * @param array $details
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function addPaymentTransaction(Order $order, $transactionId, array $details = [])
    {
        /** @var \Magento\Framework\DataObject|\Magento\Sales\Api\Data\OrderPaymentInterface |mixed|null $payment */
        $payment = $order->getPayment();

        /** @var \Magento\Sales\Api\Data\TransactionInterface $transaction */
        $transaction = $this->transactionBuilder
            ->setPayment($payment)->setOrder($order)
            ->setTransactionId($transactionId)
            ->setAdditionalInformation([Transaction::RAW_DETAILS => (array)$details])
            ->setFailSafe(true)
            ->build(Transaction::TYPE_CAPTURE);
        $transaction->setIsClosed(0);
        return $transaction;
    }

    /**
     * VerifyPaymentData function
     *
     * @param array $params
     * @param Order $currentOrder
     * @return mixed|string|void
     * @throws \Nexi\Checkout\Exceptions\CheckoutException
     */
    public function verifyPaymentData($params, $currentOrder)
    {
        // TODO: Create nexi specific
    }
}

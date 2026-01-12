<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook\Data;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;

class WebhookDataLoader
{
    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private TransactionRepositoryInterface $transactionRepository
    ) {
    }

    /**
     * LoadTransactionByTxnId function
     *
     * @param string $txnId
     * @param string $txnType
     *
     * @return TransactionInterface|null
     */
    public function getTransactionByPaymentId(
        string $txnId,
        string $txnType = TransactionInterface::TYPE_PAYMENT
    ): ?TransactionInterface {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('txn_id', $txnId, 'eq')
            ->addFilter('txn_type', $txnType, 'eq')
            ->create();

        $transactions = $this->transactionRepository->getList($searchCriteria)->getItems();

        if ($transactions === null || count($transactions) !== 1) {
            return null;
        }

        return reset($transactions);
    }

    /**
     * LoadTransactionOrderId function
     *
     * @param int $orderId
     * @param string $txnType
     *
     * @return TransactionInterface
     * @throws NotFoundException
     */
    public function getTransactionByOrderId(
        int $orderId,
        string $txnType = TransactionInterface::TYPE_PAYMENT
    ): TransactionInterface {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $orderId, 'eq')
            ->addFilter('txn_type', $txnType, 'eq')
            ->create();

        $transactions = $this->transactionRepository->getList($searchCriteria)->getItems();

        if ($transactions === null || count($transactions) === 0) {
            throw new NotFoundException(__('Transaction not found for payment ID.'));
        }

        return reset($transactions);
    }

    /**
     * LoadOrderByPaymentId function.
     *
     * @param string $paymentId
     *
     * @return Order
     * @throws NotFoundException
     */
    public function loadOrderByPaymentId(string $paymentId): Order
    {
        $transaction = $this->getTransactionByPaymentId($paymentId);
        if ($transaction === null) {
            throw new NotFoundException(__('Transaction not found for payment ID: %1', $paymentId));
        }
        $order = $transaction->getOrder();

        return $order;
    }
}

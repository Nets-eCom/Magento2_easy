<?php

namespace Nexi\Checkout\Model\Webhook\Data;


use Magento\Checkout\Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;

class WebhookDataLoader
{
    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        private SearchCriteriaBuilder          $searchCriteriaBuilder,
        private TransactionRepositoryInterface $transactionRepository
    ) {
    }

    /**
     * LoadTransactionByPaymentId function
     *
     * @param $paymentId
     * @return TransactionInterface[]
     */
    public function loadTransactionByPaymentId($paymentId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('txn_id', $paymentId, 'eq')
            ->create();

        $transaction = $this->transactionRepository->getList($searchCriteria)->getItems();

        return reset($transaction);
    }

    /**
     * LoadOrderByPaymentId function.
     *
     * @param $paymentId
     * @return mixed
     * @throws LocalizedException
     */
    public function loadOrderByPaymentId($paymentId)
    {
        $transaction = $this->loadTransactionByPaymentId($paymentId);
        $order       = $transaction->getOrder();

        return $order;
    }
}

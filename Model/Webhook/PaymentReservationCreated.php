<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;


use Magento\Checkout\Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;

class PaymentReservationCreated
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private SearchCriteriaBuilder          $searchCriteriaBuilder,
        private OrderRepositoryInterface       $orderRepository
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.reservation.created.v2' event.
     * @param $response
     * @return void
     * @throws Exception
     */
    public function processWebhook($response)
    {
        $params = json_decode('{"id":"d60fd4bbaad6454a8c2a4377601c969c","timestamp":"2025-02-24T13:58:29.1396+00:00","merchantNumber":100065206,"event":"payment.reservation.created.v2","data":{"paymentMethod":"Visa","paymentType":"CARD","amount":{"amount":5780,"currency":"EUR"},"paymentId":"f369621ef1b149b5b90b65504506eb75"}}', true);
        $transaction = $this->loadTransactionByPaymentId($params['data']['paymentId']);

        $order = $this->orderRepository->get(reset($transaction)->getOrderId());
        $order->getPayment()->setAdditionalInformation('selected_payment_method', $params['data']['paymentMethod']);

        $this->processOrder($order);
        $this->orderRepository->save($order);
    }

    /**
     * LoadTransactionByPaymentId function
     *
     * @param $paymentId
     * @return \Magento\Sales\Api\Data\TransactionInterface[]
     * @throws Exception
     */
    private function loadTransactionByPaymentId($paymentId)
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('txn_id', $paymentId, 'eq')
                ->create();
            $transaction = $this->transactionRepository->getList($searchCriteria)->getItems();
        } catch (\Exception $e) {
            throw new Exception(__($e->getMessage()));
        }

        return $transaction;
    }

    /**
     * ProcessOrder function.
     * @param $order
     * @return void
     * @throws Exception
     */
    private function processOrder($order): void
    {
        try {
            if ($order->getStatus() === Order::STATE_NEW) {
                $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
            }
        } catch (\Exception $e) {
            throw new Exception(__($e->getMessage()));
        }
    }
}

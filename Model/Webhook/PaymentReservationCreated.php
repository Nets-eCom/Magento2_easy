<?php

namespace Nexi\Checkout\Model\Webhook;

use Magento\Checkout\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentReservationCreated
{
    /**
     * PaymentReservationCreated constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param WebhookDataLoader $webhookDataLoader
     */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private WebhookDataLoader $webhookDataLoader,
        private Builder $transactionBuilder
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.reservation.created.v2' event.
     *
     * @param $webhookDada
     *
     * @return void
     * @throws Exception
     * @throws LocalizedException
     * @throws \Exception
     */
    public function processWebhook($webhookDada): void
    {
        $paymentId          = $webhookDada['data']['paymentId'];
        $paymentTransaction = $this->webhookDataLoader->loadTransactionByPaymentId($paymentId);
        if (!$paymentTransaction) {
            throw new \Exception('Payment transaction not found.');
        }

        $order = $paymentTransaction->getOrder();

        $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
        $paymentTransaction = $this->transactionBuilder->build(
            $webhookDada['id'],
            $order,
            ['payment_id' => $paymentId],
            TransactionInterface::TYPE_AUTH
        );
        $paymentTransaction->setIsClosed(0);
        $paymentTransaction->setParentTxnId($paymentId);
        $paymentTransaction->setParentId($paymentTransaction->getTransactionId());

        $order->getPayment()->addTransactionCommentsToOrder(
            $paymentTransaction,
            __('Payment reservation created.')
        );

        $this->orderRepository->save($order);
    }

    /**
     * ProcessOrder function.
     *
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

<?php

namespace Nexi\Checkout\Model\Webhook;

use Magento\Checkout\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentReservationCreated implements WebhookProcessorInterface
{
    /**
     * PaymentReservationCreated constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param WebhookDataLoader $webhookDataLoader
     * @param Builder $transactionBuilder
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
     * @param $webhookData
     *
     * @return void
     * @throws Exception
     * @throws LocalizedException
     * @throws \Exception
     */
    public function processWebhook($webhookData): void
    {
        $paymentId          = $webhookData['data']['paymentId'];
        $paymentTransaction = $this->webhookDataLoader->getTransactionByPaymentId($paymentId);
        if (!$paymentTransaction) {
            throw new \Exception('Payment transaction not found.');
        }

        $order = $paymentTransaction->getOrder();

        $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
        $reservationTransaction = $this->transactionBuilder->build(
            $webhookData['id'],
            $order,
            ['payment_id' => $paymentId],
            TransactionInterface::TYPE_AUTH
        );
        $reservationTransaction->setIsClosed(0);
        $reservationTransaction->setParentTxnId($paymentId);
        $reservationTransaction->setParentId($paymentTransaction->getTransactionId());

        $order->getPayment()->addTransactionCommentsToOrder(
            $reservationTransaction,
            __('Payment reservation created.')
        );

        $this->orderRepository->save($order);
    }
}

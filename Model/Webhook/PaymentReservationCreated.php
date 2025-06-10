<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentReservationCreated implements WebhookProcessorInterface
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param WebhookDataLoader $webhookDataLoader
     * @param Builder $transactionBuilder
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly WebhookDataLoader $webhookDataLoader,
        private readonly Builder $transactionBuilder
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.reservation.created.v2' event.
     *
     * @param array $webhookData
     *
     * @return void
     * @throws NotFoundException
     */
    public function processWebhook(array $webhookData): void
    {
        $paymentId          = $webhookData['data']['paymentId'];
        $paymentTransaction = $this->webhookDataLoader->getTransactionByPaymentId($paymentId);
        if (!$paymentTransaction) {
            throw new NotFoundException(__('Payment transaction not found for %1.', $paymentId));
        }

        /** @var \Magento\Sales\Model\Order $order */
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

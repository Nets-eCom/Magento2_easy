<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\SubscriptionManagement;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use Nexi\Checkout\Setup\Patch\Data\AddPaymentAuthorizedOrderStatus;

class PaymentReservationCreated implements WebhookProcessorInterface
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param WebhookDataLoader $webhookDataLoader
     * @param Builder $transactionBuilder
     * @param Comment $comment
     * @param SubscriptionManagement $subscriptionManagement
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly WebhookDataLoader        $webhookDataLoader,
        private readonly Builder                  $transactionBuilder,
        private readonly Comment                  $comment,
        private readonly SubscriptionManagement $subscriptionManagement
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.reservation.created.v2' event.
     *
     * @param array $webhookData
     *
     * @return void
     * @throws NotFoundException
     * @throws CouldNotSaveException
     */
    public function processWebhook(array $webhookData): void
    {
        $paymentId          = $webhookData['data']['paymentId'];
        $paymentTransaction = $this->webhookDataLoader->getTransactionByPaymentId($paymentId);
        if (!$paymentTransaction) {
            throw new NotFoundException(__('Payment transaction not found for %1.', $paymentId));
        }

        /** @var Order $order */
        $order = $paymentTransaction->getOrder();

        if ($this->authorizationAlreadyExists($webhookData['id'])) {
            return;
        }

        $order->setState(Order::STATE_PENDING_PAYMENT)
            ->setStatus(AddPaymentAuthorizedOrderStatus::STATUS_NEXI_AUTHORIZED);

        $reservationTransaction = $this->transactionBuilder->build(
            $webhookData['id'],
            $order,
            ['payment_id' => $paymentId],
            TransactionInterface::TYPE_AUTH
        );
        $reservationTransaction->setIsClosed(0);
        $reservationTransaction->setParentTxnId($paymentId);
        $reservationTransaction->setParentId($paymentTransaction->getTransactionId());

        if (isset($webhookData['data']['subscriptionId'])) {
            $order->getPayment()->setAdditionalInformation('subscription_id', $webhookData['data']['subscriptionId']);
            $this->subscriptionManagement->processSubscription($order, $webhookData['data']['subscriptionId']);
        }

        $payment = $order->getPayment();
        $amount = $webhookData['data']['amount']['amount'] / 100;
        $amount = $payment->formatAmount($amount, true);
        $payment->setBaseAmountAuthorized($amount);

        $this->orderRepository->save($order);

        $this->saveComment($paymentId, $webhookData, $order);
    }

    /**
     * Checks if an authorization already exists for the given ID.
     *
     * @param mixed $id
     *
     * @return bool
     */
    private function authorizationAlreadyExists(mixed $id)
    {
        return $this->webhookDataLoader->getTransactionByPaymentId($id, TransactionInterface::TYPE_AUTH) !== null;
    }

    /**
     * @param mixed $paymentId
     * @param array $webhookData
     * @param Order $order
     *
     * @return void
     * @throws CouldNotSaveException
     */
    private function saveComment(mixed $paymentId, array $webhookData, Order $order): void
    {
        $this->comment->saveComment(
            __(
                'Webhook Received. Payment reservation created for payment ID: %1'
                . '<br/>Reservation Id: %2'
                . '<br/>Amount: %3 %4.',
                $paymentId,
                $webhookData['id'],
                number_format($webhookData['data']['amount']['amount'] / 100, 2, '.', ''),
                $webhookData['data']['amount']['currency']
            ),
            $order
        );
    }
}

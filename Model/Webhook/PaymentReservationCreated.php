<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Block\Info\Nexi;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\SubscriptionManagement;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use Nexi\Checkout\Setup\Patch\Data\AddPaymentAuthorizedOrderStatus;
use NexiCheckout\Model\Webhook\Data\ReservationCreatedData;
use NexiCheckout\Model\Webhook\ReservationCreated;
use NexiCheckout\Model\Webhook\WebhookInterface;
use Psr\Log\LoggerInterface;

class PaymentReservationCreated implements WebhookProcessorInterface
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param WebhookDataLoader $webhookDataLoader
     * @param Builder $transactionBuilder
     * @param Comment $comment
     * @param SubscriptionManagement $subscriptionManagement
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly WebhookDataLoader $webhookDataLoader,
        private readonly Builder $transactionBuilder,
        private readonly Comment $comment,
        private readonly SubscriptionManagement $subscriptionManagement,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.reservation.created.v2' event.
     *
     * @param WebhookInterface $webhook
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws NotFoundException
     */
    public function processWebhook(WebhookInterface $webhook): void
    {
        /* @var ReservationCreatedData $data */
        $data = $webhook->getData();
        $paymentId = $data->getPaymentId();
        $paymentTransaction = $this->webhookDataLoader->getTransactionByPaymentId($paymentId);
        if (!$paymentTransaction) {
            throw new NotFoundException(__('Payment transaction not found for %1.', $paymentId));
        }

        /** @var Order $order */
        $order = $paymentTransaction->getOrder();

        if ($this->authorizationAlreadyExists($webhook->getId())) {
            return;
        }

        $order->setState(Order::STATE_PENDING_PAYMENT)
            ->setStatus(AddPaymentAuthorizedOrderStatus::STATUS_NEXI_AUTHORIZED);
        $this->setSelectedPaymentMethodData($order, $data);

        $reservationTransaction = $this->transactionBuilder->build(
            $webhook->getId(),
            $order,
            ['payment_id' => $paymentId],
            TransactionInterface::TYPE_AUTH
        );
        $reservationTransaction->setIsClosed(0);
        $reservationTransaction->setParentTxnId($paymentId);
        $reservationTransaction->setParentId($paymentTransaction->getTransactionId());

        if ($data->getSubscriptionId() !== null) {
            $order->getPayment()->setAdditionalInformation('subscription_id', $data->getSubscriptionId());
            $this->subscriptionManagement->processSubscription($order, $data->getSubscriptionId());
        }

        $payment = $order->getPayment();
        $amount = $data->getAmount()->getAmount() / 100;
        $amount = $payment->formatAmount($amount, true);
        $payment->setBaseAmountAuthorized($amount);

        $this->orderRepository->save($order);

        $this->saveComment($paymentId, $data, $order);
    }

    /**
     * Checks if an authorization already exists for the given ID.
     *
     * @param string $id
     *
     * @return bool
     */
    private function authorizationAlreadyExists(string $id): bool
    {
        return $this->webhookDataLoader->getTransactionByPaymentId($id, TransactionInterface::TYPE_AUTH) !== null;
    }

    /**
     * Saves a comment in the order with details from the webhook data.
     *
     * @param mixed $paymentId
     * @param ReservationCreatedData $webhookData
     * @param Order $order
     *
     * @return void
     */
    private function saveComment(mixed $paymentId, ReservationCreatedData $webhookData, Order $order): void
    {
        $this->comment->saveComment(
            __(
                'Webhook Received. Payment reservation created for payment ID: %1'
                . '<br/>Reservation Id: %2'
                . '<br/>Amount: %3 %4.',
                $paymentId,
                $webhookData->getPaymentId(),
                number_format($webhookData->getAmount()->getAmount() / 100, 2, '.', ''),
                $webhookData->getAmount()->getCurrency()
            ),
            $order
        );
    }

    /**
     * Sets the selected payment method data in the order's payment information.
     *
     * @param Order $order
     * @param ReservationCreatedData $webhookData
     *
     * @return void
     */
    private function setSelectedPaymentMethodData(Order $order, ReservationCreatedData $webhookData): void
    {
        try {
            $payment = $order->getPayment();
            if ($payment) {
                $payment->setAdditionalInformation(
                    Nexi::SELECTED_PATMENT_METHOD,
                    $webhookData->getPaymentMethod()
                );
                $payment->setAdditionalInformation(
                    Nexi::SELECTED_PATMENT_TYPE,
                    $webhookData->getPaymentType()
                );
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}

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

class PaymentReservationCreated implements WebhookProcessorInterface
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param WebhookDataLoader $webhookDataLoader
     * @param Builder $transactionBuilder
     * @param Comment $comment
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

        if ($this->authorizationAlreadyExists($webhookData['id'])) {
            return;
        }

        $order->setState(Order::STATE_PENDING_PAYMENT)
            ->setStatus(AddPaymentAuthorizedOrderStatus::STATUS_NEXI_AUTHORIZED);
        $this->setSelectedPaymentMethodData($order, $webhookData);

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
     * Sets the selected payment method data in the order's payment information.
     *
     * @param Order $order
     * @param array $webhookData
     * @return void
     */
    private function setSelectedPaymentMethodData($order, $webhookData): void
    {
        try {
            $payment = $order->getPayment();
            if ($payment) {
                $payment->setAdditionalInformation(
                    Nexi::SELECTED_PATMENT_METHOD,
                    $webhookData['data']['paymentMethod'] ?? ''
                );
                $payment->setAdditionalInformation(
                    Nexi::SELECTED_PATMENT_TYPE,
                    $webhookData['data']['paymentType'] ?? ''
                );
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}

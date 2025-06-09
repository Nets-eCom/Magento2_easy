<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentCreated implements WebhookProcessorInterface
{
    /**
     * PaymentCreated constructor.
     *
     * @param Builder $transactionBuilder
     * @param CollectionFactory $orderCollectionFactory
     * @param WebhookDataLoader $webhookDataLoader
     * @param OrderRepositoryInterface $orderRepository
     * @param Comment $comment
     */
    public function __construct(
        private readonly Builder $transactionBuilder,
        private readonly CollectionFactory $orderCollectionFactory,
        private readonly WebhookDataLoader $webhookDataLoader,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Comment $comment
    ) {
    }

    /**
     * PaymentCreated webhook service.
     *
     * @param array $webhookData
     *
     * @return void
     * @throws CouldNotSaveException
     */
    public function processWebhook(array $webhookData): void
    {
        $transaction = $this->webhookDataLoader->getTransactionByPaymentId($webhookData['data']['paymentId']);

        $order = $this->orderCollectionFactory->create()->addFieldToFilter(
            'increment_id',
            $webhookData['data']['order']['reference']
        )->getFirstItem();

        $this->comment->saveComment(
            __('Webhook Received. Payment created for payment ID: %1', $webhookData['data']['paymentId']),
            $order
        );

        if (!$transaction) {
            $this->createPaymentTransaction($order, $webhookData['data']['paymentId']);
            $this->orderRepository->save($order);
        }
    }

    /**
     * ProcessOrder function.
     *
     * @param Order $order
     * @param string $paymentId
     *
     * @return void
     */
    private function createPaymentTransaction(Order $order, string $paymentId): void
    {
        if ($order->getState() !== Order::STATE_NEW) {
            return;
        }
        $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
        $paymentTransaction = $this->transactionBuilder
            ->build(
                $paymentId,
                $order,
                [
                    'payment_id' => $paymentId
                ],
                TransactionInterface::TYPE_PAYMENT
            );
        $order->getPayment()->addTransactionCommentsToOrder(
            $paymentTransaction,
            __('Payment created in Nexi Gateway.')
        );
    }
}

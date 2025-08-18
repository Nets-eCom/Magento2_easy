<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Framework\Exception\NotFoundException;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Order\Comment;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory as PaymentCollectionFactory;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use NexiCheckout\Model\Webhook\Data\PaymentCreatedData;
use NexiCheckout\Model\Webhook\WebhookInterface;

class PaymentCreated implements WebhookProcessorInterface
{
    /**
     * PaymentCreated constructor.
     *
     * @param Builder $transactionBuilder
     * @param CollectionFactory $orderCollectionFactory
     * @param WebhookDataLoader $webhookDataLoader
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentCollectionFactory $paymentCollectionFactory
     * @param Comment $comment
     */
    public function __construct(
        private readonly Builder $transactionBuilder,
        private readonly CollectionFactory $orderCollectionFactory,
        private readonly WebhookDataLoader $webhookDataLoader,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PaymentCollectionFactory $paymentCollectionFactory,
        private readonly Comment $comment,
    ) {
    }

    /**
     * PaymentCreated webhook service.
     *
     * @param WebhookInterface $webhook
     *
     * @return void
     * @throws NotFoundException
     */
    public function processWebhook(WebhookInterface $webhook): void
    {
        /** @var PaymentCreatedData $data */
        $data = $webhook->getData();
        $paymentId = $data->getPaymentId();
        $transaction = $this->webhookDataLoader->getTransactionByPaymentId($paymentId);

        $orderReference = $data->getOrder()->getReference();

        if ($orderReference === null) {
            $order = $this->getOrderByPaymentId($paymentId);
            if (!$order->getId()) {
                throw new NotFoundException(__('Order not found for payment ID: %1', $paymentId));
            }
        } else {
            $order = $this->orderCollectionFactory->create()->addFieldToFilter(
                'increment_id',
                $orderReference
            )->getFirstItem();
        }

        if (!$transaction) {
            $this->createPaymentTransaction($order, $data->getPaymentId());
            $this->orderRepository->save($order);
            $this->comment->saveComment(
                __(
                    'Webhook Received. Payment created for Payment ID: %1'
                    . '<br />Amount: %2 %3.',
                    $data->getPaymentId(),
                    number_format($data->getOrder()->getAmount()->getAmount() / 100, 2, '.', ''),
                    $data->getOrder()->getAmount()->getCurrency()
                ),
                $order
            );
        }
    }

    /**
     * Get order by payment id.
     *
     * @param string $paymentId
     *
     * @return Order
     */
    private function getOrderByPaymentId(string $paymentId)
    {
        $payment = $this->paymentCollectionFactory->create()
            ->addFieldToFilter('last_trans_id', $paymentId)
            ->getFirstItem();
        $orderId = $payment->getParentId();

        return $this->orderCollectionFactory->create()->addFieldToFilter('entity_id', $orderId)->getFirstItem();
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
        $this->transactionBuilder
            ->build(
                $paymentId,
                $order,
                [
                    'payment_id' => $paymentId
                ],
                TransactionInterface::TYPE_PAYMENT
            );
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Order\Comment;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory as PaymentCollectionFactory;
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
     * @param array $webhookData
     *
     * @return void
     * @throws NotFoundException
     */
    public function processWebhook(array $webhookData): void
    {
        $paymentId   = $webhookData['data']['paymentId'];
        $transaction = $this->webhookDataLoader->getTransactionByPaymentId($paymentId);

        $orderReference = $webhookData['data']['order']['reference'] ?? null;

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
            $this->createPaymentTransaction($order, $webhookData['data']['paymentId']);
            $this->orderRepository->save($order);
            $this->comment->saveComment(
                __(
                    'Webhook Received. Payment created for Payment ID: %1'
                    . '<br />Amount: %2 %3.',
                    $webhookData['data']['paymentId'],
                    number_format($webhookData['data']['order']['amount']['amount'] / 100, 2, '.', ''),
                    $webhookData['data']['order']['amount']['currency']
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
        $paymentTransaction = $this->transactionBuilder
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

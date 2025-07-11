<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Nexi\Checkout\Gateway\AmountConverter;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentRefundCompleted implements WebhookProcessorInterface
{
    /**
     * @param WebhookDataLoader $webhookDataLoader
     * @param Builder $transactionBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param CreditmemoFactory $creditmemoFactory
     * @param CreditmemoManagementInterface $creditmemoManagement
     * @param AmountConverter $amountConverter
     * @param Comment $comment
     */
    public function __construct(
        private readonly WebhookDataLoader $webhookDataLoader,
        private readonly Builder $transactionBuilder,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CreditmemoFactory $creditmemoFactory,
        private readonly CreditmemoManagementInterface $creditmemoManagement,
        private readonly AmountConverter $amountConverter,
        private readonly Comment $comment,
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.refund.completed' event.
     *
     * @param array $webhookData
     *
     * @return void
     * @throws LocalizedException
     */
    public function processWebhook(array $webhookData): void
    {
        $order = $this->webhookDataLoader->loadOrderByPaymentId($webhookData['data']['paymentId']);

        $this->comment->saveComment(
            __(
                'Webhook Received. Refund created for payment ID: %1, <br/>Refund ID: %2',
                $webhookData['data']['paymentId'],
                $webhookData['data']['refundId']
            ),
            $order
        );

        if ($this->findRefundTransaction($webhookData['data']['refundId'])) {
            return;
        }

        $refund = $this->transactionBuilder
            ->build(
                $webhookData['data']['refundId'],
                $order,
                ['payment_id' => $webhookData['data']['paymentId']],
                TransactionInterface::TYPE_REFUND
            )->setParentTxnId($webhookData['data']['paymentId'])
            ->setAdditionalInformation('details', json_encode($webhookData));

        $order->getPayment()->addTransaction($refund);

        if ($this->isFullRefund($webhookData, $order)) {
            $this->processFullRefund($webhookData, $order);
        } else {
            $order->addCommentToStatusHistory(
                'Partial refund created for order. ' .
                'Automatic credit memo processing is not supported for partial refunds. ' .
                'You can create a credit memo manually with offline refund if needed.',
            );
        }

        $order->getPayment()->addTransactionCommentsToOrder(
            $refund,
            __('Payment refund created, amount: %1', $webhookData['data']['amount']['amount'] / 100)
        );

        $this->orderRepository->save($order);
    }

    /**
     * Create creditmemo for whole order
     *
     * @param array $webhookData
     * @param Order $order
     *
     * @return void
     */
    private function processFullRefund(array $webhookData, Order $order): void
    {
        $creditmemo = $this->creditmemoFactory->createByOrder($order);
        $creditmemo->setTransactionId($webhookData['data']['refundId']);

        $this->creditmemoManagement->refund($creditmemo);
    }

    /**
     * Amount check
     *
     * @param array $webhookData
     * @param Order $order
     *
     * @return bool
     */
    private function isFullRefund(array $webhookData, Order $order): bool
    {
        $grandTotal = $this->amountConverter->convertToNexiAmount($order->getGrandTotal());

        return $grandTotal === $webhookData['data']['amount']['amount'];
    }

    /**
     * Finds and retrieves a refund transaction based on the provided payment ID.
     *
     * @param string $id
     *
     * @return TransactionInterface|null
     */
    private function findRefundTransaction(string $id): ?TransactionInterface
    {
        return $this->webhookDataLoader->getTransactionByPaymentId($id, TransactionInterface::TYPE_REFUND);
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Nexi\Checkout\Gateway\AmountConverter;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use NexiCheckout\Model\Webhook\Data\RefundCompletedData;
use NexiCheckout\Model\Webhook\WebhookInterface;

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
     * @param WebhookInterface $webhook
     *
     * @return void
     */
    public function processWebhook(WebhookInterface $webhook): void
    {
        /** @var RefundCompletedData $data */
        $data = $webhook->getData();
        $paymentId      = $data->getPaymentId();
        $refundId       = $data->getRefundId();
        $refundAmount   = number_format($data->getAmount()->getAmount() / 100, 2, '.', '');
        $refundCurrency = $data->getAmount()->getCurrency();

        $order = $this->webhookDataLoader->loadOrderByPaymentId($paymentId);

        if ($this->findRefundTransaction($refundId)) {
            return;
        }

        $this->validateChargeTransactionExists($order);

        $refundTransaction = $this->createRefundTransaction($refundId, $order, $paymentId, $data);
        $order->getPayment()->addTransaction($refundTransaction);

        if ($this->canRefund($data, $order) && $order->canCreditmemo()) {
            $this->processFullRefund($data, $order);
        } else {
            $order->addCommentToStatusHistory(
                'Partial refund created for payment. ' .
                'Automatic credit memo processing is not supported for this case. ' .
                'You can still create a credit memo manually with offline refund.'
            );
        }

        $this->orderRepository->save($order);

        $this->comment->saveComment(
            $this->createRefundComment($paymentId, $refundId, $refundAmount, $refundCurrency),
            $order
        );
    }

    /**
     * Create a refund comment for the order.
     *
     * @param string $paymentId
     * @param string $refundId
     * @param string $refundAmount
     * @param string $currency
     *
     * @return Phrase
     */
    private function createRefundComment(
        string $paymentId,
        string $refundId,
        string $refundAmount,
        string $currency
    ): Phrase {
        return __(
            'Webhook Received. Refund created for payment ID: %1'
            . '<br/>Refund ID: %2'
            . '<br/>Amount: %3 %4',
            $paymentId,
            $refundId,
            $refundAmount,
            $currency
        );
    }

    /**
     * Build a refund transaction based on webhook data.
     *
     * @param string $refundId
     * @param Order $order
     * @param string $paymentId
     * @param array $webhookData
     *
     * @return TransactionInterface
     * @throws LocalizedException
     */
    private function createRefundTransaction(
        string $refundId,
        Order $order,
        string $paymentId,
        array $webhookData
    ): TransactionInterface {
        return $this->transactionBuilder
            ->build(
                $refundId,
                $order,
                ['payment_id' => $paymentId],
                TransactionInterface::TYPE_REFUND
            )
            ->setParentTxnId($paymentId)
            ->setAdditionalInformation('details', json_encode($webhookData));
    }

    /**
     * Create creditmemo for whole order
     *
     * @param RefundCompletedData $webhookData
     * @param Order $order
     *
     * @return void
     */
    private function processFullRefund(RefundCompletedData $webhookData, Order $order): void
    {
        $creditmemo = $this->creditmemoFactory->createByOrder($order);
        $creditmemo->setTransactionId($webhookData->getRefundId());

        $this->creditmemoManagement->refund($creditmemo);
    }

    /**
     * Amount check
     *
     * @param RefundCompletedData $webhookData
     * @param Order $order
     *
     * @return bool
     */
    private function canRefund(RefundCompletedData $webhookData, Order $order): bool
    {
        $grandTotal    = $this->amountConverter->convertToNexiAmount($order->getGrandTotal());
        $totalRefunded = $order->getTotalRefunded();

        return $grandTotal === $webhookData->getAmount()->getAmount()
            && 0 == $totalRefunded;
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

    /**
     * Check if charge transaction exists for the given payment ID.
     *
     * @param Order $order
     *
     * @return void
     * @throws NotFoundException
     */
    private function validateChargeTransactionExists(Order $order): void
    {
        $this->webhookDataLoader->getTransactionByOrderId(
            (int)$order->getId(),
            TransactionInterface::TYPE_CAPTURE
        );
    }
}

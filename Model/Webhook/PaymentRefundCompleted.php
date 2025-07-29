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
        $paymentId    = $webhookData['data']['paymentId'];
        $refundId     = $webhookData['data']['refundId'];
        $refundAmount = number_format($webhookData['data']['amount']['amount'] / 100, 2, '.', '');
        $refundCurrency = $webhookData['data']['amount']['currency'];

        $order = $this->webhookDataLoader->loadOrderByPaymentId($paymentId);

        $this->comment->saveComment(
            $this->createRefundComment($paymentId, $refundId, $refundAmount, $refundCurrency),
            $order
        );

        if ($this->findRefundTransaction($refundId)) {
            return;
        }

        $this->validateChargeTransactionExists($order);

        $refundTransaction = $this->createRefundTransaction($refundId, $order, $paymentId, $webhookData);
        $order->getPayment()->addTransaction($refundTransaction);

        if (!$order->canCreditmemo()) {
            $this->orderRepository->save($order);
            return;
        }

        if ($this->canRefund($webhookData, $order)) {
            $this->processFullRefund($webhookData, $order);
        } else {
            $order->addCommentToStatusHistory(
                'Partial refund created for payment. ' .
                'Automatic credit memo processing is not supported for this case. ' .
                'You can still create a credit memo manually with offline refund.'
            );
        }

        $this->orderRepository->save($order);
    }

    /**
     * Create a refund comment for the order.
     *
     * @param string $paymentId
     * @param string $refundId
     * @param float $refundAmount
     * @param string $currency
     *
     * @return Phrase
     */
    private function createRefundComment(
        string $paymentId,
        string $refundId,
        float $refundAmount,
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
    private function canRefund(array $webhookData, Order $order): bool
    {
        $grandTotal    = $this->amountConverter->convertToNexiAmount($order->getGrandTotal());
        $totalRefunded = $order->getTotalRefunded();

        return $grandTotal === $webhookData['data']['amount']['amount']
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

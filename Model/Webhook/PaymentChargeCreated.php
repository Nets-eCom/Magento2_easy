<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\AmountConverter;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use Nexi\Checkout\Setup\Patch\Data\AddPaymentAuthorizedOrderStatus;
use NexiCheckout\Model\Webhook\ChargeCreated;
use NexiCheckout\Model\Webhook\Shared\Data;
use NexiCheckout\Model\Webhook\WebhookInterface;

class PaymentChargeCreated implements WebhookProcessorInterface
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param WebhookDataLoader $webhookDataLoader
     * @param Builder $transactionBuilder
     * @param Comment $comment
     * @param AmountConverter $amountConverter
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly WebhookDataLoader $webhookDataLoader,
        private readonly Builder $transactionBuilder,
        private readonly Comment $comment,
        private readonly AmountConverter $amountConverter,
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.charge.created.v2' event.
     *
     * @param WebhookInterface $webhook
     *
     * @return void
     * @throws NotFoundException
     */
    public function processWebhook(WebhookInterface $webhook): void
    {
        $order = $this->webhookDataLoader->loadOrderByPaymentId($webhook->getData()->getPaymentId());
        $this->processOrder($order, $webhook);
        $this->orderRepository->save($order);
    }

    /**
     * ProcessOrder function.
     *
     * @param Order $order
     * @param ChargeCreated $webhook
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws NotFoundException
     */
    private function processOrder(Order $order, ChargeCreated $webhook): void
    {
        $reservationTxn = $this->webhookDataLoader->getTransactionByOrderId(
            (int)$order->getId(),
            TransactionInterface::TYPE_AUTH
        );

        $chargeTxnId = $webhook->getData()->getChargeId();

        if ($this->webhookDataLoader->getTransactionByPaymentId($chargeTxnId, TransactionInterface::TYPE_CAPTURE)) {
            return;
        }

        $chargeTransaction = $this->transactionBuilder
            ->build(
                $chargeTxnId,
                $order,
                [
                    'payment_id' => $webhook->getData()->getPaymentId(),
                    'webhook'    => json_encode($webhook, JSON_PRETTY_PRINT),
                ],
                TransactionInterface::TYPE_CAPTURE
            )->setParentId($reservationTxn->getTransactionId())
            ->setParentTxnId($reservationTxn->getTxnId());

        $this->saveOrderHistoryComment($webhook->getData(), $order);

        if ($this->isFullCharge($webhook, $order)) {
            if ($order->getStatus() !== AddPaymentAuthorizedOrderStatus::STATUS_NEXI_AUTHORIZED) {
                throw new Exception('Order status is not authorized.');
            }
            $this->fullInvoice($order, $chargeTxnId);
        } else {
            $order->addCommentToStatusHistory(
                'Partial charge received from the Nexi | Nets Portal gateway. ' .
                'The order processing could not be completed automatically. '
            );
        }

        $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
    }

    /**
     * Validate charge transaction.
     *
     * @param ChargeCreated $webhook
     * @param Order $order
     *
     * @return bool
     */
    private function isFullCharge(ChargeCreated $webhook, Order $order): bool
    {
        $grandTotalConverted = (int) $this->amountConverter->convertToNexiAmount($order->getGrandTotal());
        $webhookAmount = (int) $webhook->getData()->getAmount()->getAmount();

        return $grandTotalConverted === $webhookAmount;
    }

    /**
     * Process
     *
     * @param Order $order
     * @param string $chargeTxnId
     *
     * @return void
     */
    public function fullInvoice(Order $order, string $chargeTxnId): void
    {
        if (!$order->canInvoice()) {
            return;
        }

        $invoice = $order->prepareInvoice();
        $invoice->register();
        $invoice->setTransactionId($chargeTxnId);
        $invoice->pay();

        $order->addRelatedObject($invoice);
    }

    /**
     * Save order history comment.
     *
     * @param Data $data
     * @param Order $order
     *
     * @return void
     */
    private function saveOrderHistoryComment(Data $data, Order $order): void
    {
        $this->comment->saveComment(
            __(
                'Webhook Received. Payment charge created for payment ID: %1'
                . '<br/>Charge ID: %2'
                . '<br/>Amount: %3 %4.',
                $data->getPaymentId(),
                $data->getChargeId(),
                number_format($data->getAmount()->getAmount() / 100, 2, '.', ''),
                $data->getAmount()->getCurrency()
            ),
            $order
        );
    }
}

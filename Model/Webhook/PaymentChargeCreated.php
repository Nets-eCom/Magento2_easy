<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\AmountConverter;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use Nexi\Checkout\Setup\Patch\Data\AddPaymentAuthorizedOrderStatus;

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
     * @param array $webhookData
     *
     * @return void
     */
    public function processWebhook(array $webhookData): void
    {
        $order = $this->webhookDataLoader->loadOrderByPaymentId($webhookData['data']['paymentId']);
        $this->processOrder($order, $webhookData);
        $this->orderRepository->save($order);
    }

    /**
     * ProcessOrder function.
     *
     * @param Order $order
     * @param array $webhookData
     *
     * @return void
     * @throws Exception
     */
    private function processOrder(Order $order, array $webhookData): void
    {
        $reservationTxn = $this->webhookDataLoader->getTransactionByOrderId(
            (int)$order->getId(),
            TransactionInterface::TYPE_AUTH
        );

        $chargeTxnId = $webhookData['data']['chargeId'];

        if ($this->webhookDataLoader->getTransactionByPaymentId($chargeTxnId, TransactionInterface::TYPE_CAPTURE)) {
            return;
        }


        $chargeTransaction = $this->transactionBuilder
            ->build(
                $chargeTxnId,
                $order,
                [
                    'payment_id' => $webhookData['data']['paymentId'],
                    'webhook'    => json_encode($webhookData, JSON_PRETTY_PRINT),
                ],
                TransactionInterface::TYPE_CAPTURE
            )->setParentId($reservationTxn->getTransactionId())
            ->setParentTxnId($reservationTxn->getTxnId());

        $this->saveOrderHistoryComment($webhookData['data'], $order);

        if ($this->isFullCharge($webhookData, $order)) {
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
     * @param array $webhookData
     * @param Order $order
     *
     * @return bool
     */
    private function isFullCharge(array $webhookData, Order $order): bool
    {
        $grandTotalConverted = (int) $this->amountConverter->convertToNexiAmount($order->getGrandTotal());
        $webhookAmount = (int) $webhookData['data']['amount']['amount'];

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
     * @param array $data
     * @param Order $order
     *
     * @return void
     * @throws CouldNotSaveException
     */
    private function saveOrderHistoryComment(array $data, Order $order): void
    {
        $this->comment->saveComment(
            __(
                'Webhook Received. Payment charge created for payment ID: %1'
                . '<br/>Charge ID: %2'
                . '<br/>Amount: %3 %4.',
                $data['paymentId'],
                $data['chargeId'],
                number_format($data['amount']['amount'] / 100, 2, '.', ''),
                $data['amount']['currency']
            ),
            $order
        );
    }
}

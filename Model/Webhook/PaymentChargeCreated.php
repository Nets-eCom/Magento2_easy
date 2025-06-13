<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Exception;
use Magento\Framework\Event\Manager;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Request\NexiCheckout\SalesDocumentItemsBuilder;
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
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly WebhookDataLoader $webhookDataLoader,
        private readonly Builder $transactionBuilder,
        private readonly Comment $comment,
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.charge.created.v2' event.
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
            __('Webhook Received. Payment charge created for payment ID: %1', $webhookData['data']['paymentId']),
            $order
        );
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
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NotFoundException
     */
    private function processOrder(Order $order, array $webhookData): void
    {
        $reservationTxn = $this->webhookDataLoader->getTransactionByOrderId(
            (int)$order->getId(),
            TransactionInterface::TYPE_AUTH
        );

        if ($order->getStatus() !== AddPaymentAuthorizedOrderStatus::STATUS_NEXI_AUTHORIZED) {
            throw new Exception('Order status is not authorized.');
        }

        $chargeTxnId = $webhookData['data']['chargeId'];

        if ($this->webhookDataLoader->getTransactionByPaymentId($chargeTxnId, TransactionInterface::TYPE_CAPTURE)) {
            throw new AlreadyExistsException(__('Transaction already exists.'));
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

        $order->getPayment()->addTransactionCommentsToOrder(
            $chargeTransaction,
            __(
                'Payment charge created, amount: %1 %2',
                $webhookData['data']['amount']['amount'] / 100,
                $webhookData['data']['amount']['currency']
            )
        );

        if ($this->isFullCharge($webhookData, $order)) {
            $this->fullInvoice($order, $chargeTxnId);
        } else {
            $order->addCommentToStatusHistory(
                'Partial charge received from the Dibs Portal gateway. ' .
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
        return (int)($order->getBaseGrandTotal() * 100) === $webhookData['data']['amount']['amount'];
    }

    /**
     * Process
     *
     * @param Order $order
     * @param string $chargeTxnId
     *
     * @return void
     * @throws LocalizedException
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
}

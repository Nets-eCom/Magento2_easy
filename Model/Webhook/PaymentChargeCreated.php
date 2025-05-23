<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Request\NexiCheckout\SalesDocumentItemsBuilder;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentChargeCreated implements WebhookProcessorInterface
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param WebhookDataLoader $webhookDataLoader
     * @param Builder $transactionBuilder
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly WebhookDataLoader $webhookDataLoader,
        private readonly Builder $transactionBuilder
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

        if ($order->getState() !== Order::STATE_PENDING_PAYMENT) {
            throw new Exception('Order state is not pending payment.');
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
            $this->partialInvoice($order, $chargeTxnId, $webhookData['data']['orderItems']);
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

    /**
     * Create partial invoice. Add shipping amount if charged
     *
     * TODO: investigate how to invoice only shipping cost in magento? probably not possible separately - without any
     * TODO: order item invoiced now its only in order history comments (if charge only for shipping)
     *
     * @param Order $order
     * @param string $chargeTxnId
     * @param array $webhookItems
     *
     * @return void
     * @throws LocalizedException
     */
    private function partialInvoice(Order $order, string $chargeTxnId, array $webhookItems): void
    {
        if ($order->canInvoice()) {
            $qtys         = [];
            $shippingItem = null;
            foreach ($webhookItems as $webhookItem) {

                if ($webhookItem['reference'] === SalesDocumentItemsBuilder::SHIPPING_COST_REFERENCE) {
                    $shippingItem = $webhookItem;
                    continue;
                }

                foreach ($order->getAllItems() as $item) {
                    if ($item->getSku() === $webhookItem['reference']) {
                        $qtys[$item->getId()] = (int)$webhookItem['quantity'];
                    }
                }
            }
            $invoice = $order->prepareInvoice($qtys);
            $invoice->setTransactionId($chargeTxnId);
            if ($shippingItem) {
                $invoice->setShippingAmount($shippingItem['netTotalAmount'] / 100);
                $invoice->setShippingInclTax($shippingItem['grossTotalAmount'] / 100);
                $invoice->setShippingTaxAmount($shippingItem['taxAmount'] / 100);
            }

            $invoice->pay();

            $invoice->register();
            $order->addRelatedObject($invoice);
        }
    }
}

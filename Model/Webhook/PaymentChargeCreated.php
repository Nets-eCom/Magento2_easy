<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Checkout\Exception;
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
     * PaymentChargeCreated constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param WebhookDataLoader $webhookDataLoader
     * @param Builder $transactionBuilder
     */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private WebhookDataLoader        $webhookDataLoader,
        private Builder                  $transactionBuilder
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.charge.created.v2' event.
     *
     * @param $webhookData
     *
     * @return void
     * @throws LocalizedException
     */
    public function processWebhook($webhookData): void
    {
        $order = $this->webhookDataLoader->loadOrderByPaymentId($webhookData['data']['paymentId']);
        $this->processOrder($order, $webhookData);

        $this->orderRepository->save($order);
    }

    /**
     * ProcessOrder function.
     *
     * @param $order
     * @param $webhookData
     *
     * @return void
     * @throws NotFoundException
     * @throws \Exception
     */
    private function processOrder($order, $webhookData): void
    {
        $reservationTxn = $this->webhookDataLoader->getTransactionByOrderId(
            $order->getId(),
            TransactionInterface::TYPE_AUTH
        );


        if ($order->getState() !== Order::STATE_PENDING_PAYMENT) {
            throw new \Exception('Order state is not pending payment.');
        }

        $chargeTxnId       = $webhookData['data']['chargeId'];
        $chargeTransaction = $this->transactionBuilder
            ->build(
                $chargeTxnId,
                $order,
                [
                    'payment_id' => $webhookData['data']['paymentId'],
                    'webhook'  => json_encode($webhookData, JSON_PRETTY_PRINT),
                ],
                TransactionInterface::TYPE_CAPTURE
            )->setParentId($reservationTxn->getTransactionId())
            ->setParentTxnId($reservationTxn->getTxnId());

        $order->getPayment()->addTransactionCommentsToOrder(
            $chargeTransaction,
            __(
                'Payment charge created, amount: %1 %2',
                $webhookData['data']['amount']['amount']/100,
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
     * Check items paid to create proper invoice.
     *
     * @param $webhookData
     * @param $order
     *
     * @return bool
     */
    private function isFullCharge(
        $webhookData, $order
    ): bool {
        return (int)($order->getBaseGrandTotal() * 100) === $webhookData['data']['amount']['amount'];
    }

    /**
     * @param $order
     * @param $chargeTxnId
     *
     * @return void
     */
    public function fullInvoice(Order $order, $chargeTxnId): void
    {
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            $invoice->register();
            $invoice->setTransactionId($chargeTxnId);
            $invoice->pay();

            $order->addRelatedObject($invoice);
        }
    }

    /**
     * Create partial invoice. Add shipping amount if charged
     * TODO: investigate how to invoice only shipping cost in magento? probably not possible separately - without any order item invoiced
     * TODO: now its only in order history comments (if charge only for shipping)
     *
     * @param Order $order
     * @param $chargeTxnId
     * @param $webhookItems
     *
     * @return void
     * @throws LocalizedException
     */
    private function partialInvoice(Order $order, $chargeTxnId, $webhookItems): void
    {
        if ($order->canInvoice()) {

            $qtys = [];
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

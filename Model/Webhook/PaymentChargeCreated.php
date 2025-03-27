<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;


use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentChargeCreated
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
     * @param $responseData
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processWebhook($responseData)
    {
        $order = $this->webhookDataLoader->loadOrderByPaymentId($responseData['paymentId']);

        $this->processOrder($order, $responseData['paymentId'], $responseData['chargeId']);

        $this->orderRepository->save($order);
    }

    /**
     * ProcessOrder function.
     *
     * @param $order
     * @param $paymentId
     * @param $chargeTxnId
     * @return void
     * @throws \Magento\Checkout\Exception
     */
    private function processOrder($order, $paymentId, $chargeTxnId): void
    {
        $transaction = $this->webhookDataLoader->loadTransactionByPaymentId($paymentId);
        if ($order->getState() === Order::STATE_PENDING_PAYMENT) {
            $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
            $chargeTransaction = $this->transactionBuilder
                ->build(
                    $chargeTxnId,
                    $order,
                    [
                        'payment_id' => $paymentId,
                        'charge_id'  => $chargeTxnId,
                    ],
                    TransactionInterface::TYPE_CAPTURE
                )->setParentId($transaction->getTransactionId())
                ->setParentTxnId($paymentId);

            if ($order->canInvoice()) {
                $invoice = $order->prepareInvoice();
                $invoice->register();
                $invoice->setTransactionId($chargeTxnId);
                $invoice->pay();

                $order->addCommentToStatusHistory(__('Nexi Payment charged successfully.'));
                $order->addRelatedObject($invoice);
            }
        }
    }
}

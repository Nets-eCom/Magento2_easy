<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;


use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataProvider;

class PaymentChargeCreated
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private WebhookDataProvider $webhookDataProvider,
        private Builder $transactionBuilder
    ) {
    }

    public function processWebhook()
    {
        $params = json_decode('{"id":"312ecc6aaa5241a28a890b2e76ef8c93","timestamp":"2025-02-24T13:58:29.1396+00:00","merchantNumber":100065206,"event":"payment.charge.created.v2","data":{"chargeId":"312ecc6aaa5241a28a890b2e76ef8c93","orderItems":[{"grossTotalAmount":5280,"name":"Orestes Yoga Pant ","netTotalAmount":5280,"quantity":1.0,"reference":"MP10-36-Green","taxRate":0,"taxAmount":0,"unit":"pcs","unitPrice":5280},{"grossTotalAmount":0,"name":"Orestes Yoga Pant -36-Green","netTotalAmount":0,"quantity":1.0,"reference":"MP10-36-Green","taxRate":0,"taxAmount":0,"unit":"pcs","unitPrice":0},{"grossTotalAmount":500,"name":"Flat Rate - Fixed","netTotalAmount":500,"quantity":1.0,"reference":"flatrate_flatrate","taxRate":0,"taxAmount":0,"unit":"pcs","unitPrice":500}],"paymentMethod":"Visa","paymentType":"CARD","amount":{"amount":5780,"currency":"EUR"},"paymentId":"f369621ef1b149b5b90b65504506eb75"}}', true);
        $order = $this->webhookDataProvider->loadOrderByPaymentId($params['data']['paymentId']);

        $this->processOrder($order, $params['data']['paymentId'], $params['data']['chargeId']);

        $this->orderRepository->save($order);
    }

    private function processOrder($order, $paymentId, $chargeTxnId): void
    {
        $transaction = $this->webhookDataProvider->loadTransactionByPaymentId($paymentId);
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

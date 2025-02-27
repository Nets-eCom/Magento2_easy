<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;


use Magento\Checkout\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataProvider;

class PaymentCreated
{
    public function __construct(
        private Builder $transactionBuilder,
        private OrderRepositoryInterface $orderRepository,
        private WebhookDataProvider $webhookDataProvider
    ) {
    }

    /**
     * PaymentCreated webhook service.
     *
     * @param $response
     * @return void
     * @throws Exception
     * @throws LocalizedException
     */
    public function processWebhook($response): void
    {
        $params = json_decode('{"id":"685dc0ca3c034c8d8ac78e88a577870a","merchantId":100065206,"timestamp":"2025-02-24T13:57:49.2851+00:00","event":"payment.created","data":{"order":{"amount":{"amount":5780,"currency":"EUR"},"reference":"000000020","orderItems":[{"grossTotalAmount":5280,"name":"Orestes Yoga Pant ","netTotalAmount":5280,"quantity":1.0,"reference":"MP10-36-Green","taxRate":0,"taxAmount":0,"unit":"pcs","unitPrice":5280},{"grossTotalAmount":0,"name":"Orestes Yoga Pant -36-Green","netTotalAmount":0,"quantity":1.0,"reference":"MP10-36-Green","taxRate":0,"taxAmount":0,"unit":"pcs","unitPrice":0},{"grossTotalAmount":500,"name":"Flat Rate - Fixed","netTotalAmount":500,"quantity":1.0,"reference":"flatrate_flatrate","taxRate":0,"taxAmount":0,"unit":"pcs","unitPrice":500}]},"paymentId":"f369621ef1b149b5b90b65504506eb75"}}', true);

        $order = $this->webhookDataProvider->loadOrder($params['data']['order']['reference']);
        $this->processOrder($order, $params['data']['paymentId']);

        $this->orderRepository->save($order);
    }

    /**
     * ProcessOrder function.
     *
     * @param $order
     * @param $paymentId
     * @return void
     * @throws Exception
     */
    private function processOrder($order, $paymentId): void
    {
        try {
            if ($order->getState() === Order::STATE_NEW) {
                $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
                $chargeTransaction = $this->transactionBuilder
                    ->build(
                        $paymentId,
                        $order,
                        [
                            'payment_id' => $paymentId
                        ],
                        TransactionInterface::TYPE_PAYMENT
                    );
            }

            $order->addCommentToStatusHistory('Payment created successfully. Payment ID: %1', $paymentId);
        } catch (\Exception $e) {
            throw new Exception(__($e->getMessage()));
        }
    }
}

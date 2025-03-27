<?php

namespace Nexi\Checkout\Model\Webhook;

use Magento\Checkout\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentReservationCreated
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private WebhookDataLoader $webhookDataLoader
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.reservation.created.v2' event.
     *
     * @param $response
     * @return void
     * @throws Exception
     * @throws LocalizedException
     */
    public function processWebhook($response)
    {
        $params = json_decode('{"id":"d60fd4bbaad6454a8c2a4377601c969c","timestamp":"2025-02-24T13:58:29.1396+00:00","merchantNumber":100065206,"event":"payment.reservation.created.v2","data":{"paymentMethod":"Visa","paymentType":"CARD","amount":{"amount":5780,"currency":"EUR"},"paymentId":"f369621ef1b149b5b90b65504506eb75"}}', true);
        $order = $this->webhookDataLoader->loadOrderByPaymentId($params['data']['paymentId']);

        $order->getPayment()->setAdditionalInformation('selected_payment_method', $params['data']['paymentMethod']);

        $this->processOrder($order);
        $this->orderRepository->save($order);
    }

    /**
     * ProcessOrder function.
     * @param $order
     * @return void
     * @throws Exception
     */
    private function processOrder($order): void
    {
        try {
            if ($order->getStatus() === Order::STATE_NEW) {
                $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
            }
        } catch (\Exception $e) {
            throw new Exception(__($e->getMessage()));
        }
    }
}

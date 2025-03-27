<?php

namespace Nexi\Checkout\Model\Webhook;

use Magento\Checkout\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentReservationCreated
{
    /**
     * PaymentReservationCreated constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param WebhookDataLoader $webhookDataLoader
     */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private WebhookDataLoader $webhookDataLoader
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.reservation.created.v2' event.
     *
     * @param $responseData
     * @return void
     * @throws Exception
     * @throws LocalizedException
     */
    public function processWebhook($responseData)
    {
        try {
            $order = $this->webhookDataLoader->loadOrderByPaymentId($responseData['paymentId']);

            $order->getPayment()->setAdditionalInformation('selected_payment_method', $responseData['paymentMethod']);

            $this->processOrder($order);
            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            throw new Exception(__($e->getMessage()));
        }
    }

    /**
     * ProcessOrder function.
     *
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

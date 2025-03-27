<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Checkout\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentCreated
{
    /**
     * PaymentCreated constructor.
     *
     * @param Builder $transactionBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param WebhookDataLoader $webhookDataLoader
     */
    public function __construct(
        private Builder $transactionBuilder,
        private OrderRepositoryInterface $orderRepository,
        private WebhookDataLoader $webhookDataLoader
    ) {
    }

    /**
     * PaymentCreated webhook service.
     *
     * @param $responseData
     * @return void
     * @throws Exception
     * @throws LocalizedException
     */
    public function processWebhook($responseData): void
    {
        try {
            $order = $this->webhookDataLoader->loadOrderByPaymentId($responseData['paymentId']);
            $this->processOrder($order, $responseData['paymentId']);

            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
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

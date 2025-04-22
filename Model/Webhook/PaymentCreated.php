<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Braintree\Exception\NotFound;
use Magento\Checkout\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory as PaymentCollectionFactory;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentCreated implements WebhookProcessorInterface
{
    /**
     * PaymentCreated constructor.
     *
     * @param Builder $transactionBuilder
     * @param CollectionFactory $orderCollectionFactory
     * @param WebhookDataLoader $webhookDataLoader
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentCollectionFactory $paymentCollectionFactory
     */
    public function __construct(
        private readonly Builder                  $transactionBuilder,
        private readonly CollectionFactory        $orderCollectionFactory,
        private readonly WebhookDataLoader        $webhookDataLoader,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PaymentCollectionFactory $paymentCollectionFactory
    ) {
    }

    /**
     * PaymentCreated webhook service.
     *
     * @param array $webhookData
     *
     * @return void
     * @throws Exception
     * @throws LocalizedException|NotFound
     */
    public function processWebhook(array $webhookData): void
    {
        $paymentId   = $webhookData['data']['paymentId'];
        $transaction = $this->webhookDataLoader->getTransactionByPaymentId($paymentId);
        $order       = null;

        if ($transaction) {
            return;
        }

        $orderReference = $webhookData['data']['order']['reference'] ?? null;

        if ($orderReference === null) {
            $order = $this->getOrderByPaymentId($paymentId);
            $orderReference = $order->getIncrementId();
        }

        if (!$order) {
            $order = $this->orderCollectionFactory->create()->addFieldToFilter(
                'increment_id',
                $orderReference
            )->getFirstItem();
        }

        $this->createPaymentTransaction($order, $paymentId);

        $this->orderRepository->save($order);
    }

    /**
     * Get order by payment id.
     *
     * @param string $paymentId
     *
     * @return Order
     * @throws NotFound
     */
    private function getOrderByPaymentId(string $paymentId)
    {
        $payment = $this->paymentCollectionFactory->create()
            ->addFieldToFilter('last_trans_id', $paymentId)
            ->getFirstItem();
        $orderId = $payment->getParentId();

        return $this->orderCollectionFactory->create()->addFieldToFilter('entity_id', $orderId)->getFirstItem();
    }

    /**
     * ProcessOrder function.
     *
     * @param Order $order
     * @param int $paymentId
     *
     * @return void
     */
    private function createPaymentTransaction($order, $paymentId): void
    {
        if ($order->getState() === Order::STATE_NEW) {
            $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
            $paymentTransaction = $this->transactionBuilder
                ->build(
                    $paymentId,
                    $order,
                    [
                        'payment_id' => $paymentId
                    ],
                    TransactionInterface::TYPE_PAYMENT
                );
            $order->getPayment()->addTransactionCommentsToOrder(
                $paymentTransaction,
                __('Payment created in Nexi Gateway.')
            );
        }
    }
}

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
     */
    public function __construct(
        private readonly Builder                    $transactionBuilder,
        private readonly CollectionFactory          $orderCollectionFactory,
        private readonly WebhookDataLoader          $webhookDataLoader,
        private readonly OrderRepositoryInterface   $orderRepository
    ) {
    }

    /**
     * PaymentCreated webhook service.
     *
     * @param $webhookData
     *
     * @return void
     * @throws Exception
     * @throws LocalizedException
     */
    public function processWebhook($webhookData): void
    {
        $transaction = $this->webhookDataLoader->getTransactionByPaymentId($webhookData['data']['paymentId']);

        if ($transaction) {
            return;
        }

        $order = $this->orderCollectionFactory->create()->addFieldToFilter(
            'increment_id',
            $webhookData['data']['order']['reference']
        )->getFirstItem();

        $this->createPaymentTransaction($order, $webhookData['data']['paymentId']);

        $this->orderRepository->save($order);
    }

    /**
     * ProcessOrder function.
     *
     * @param $order
     * @param $paymentId
     *
     * @return void
     * @throws Exception
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

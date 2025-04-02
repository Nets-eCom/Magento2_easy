<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;


use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentRefundCompleted implements WebhookProcessorInterface
{
    /**
     * PaymentRefundCompleted constructor.
     *
     * @param WebhookDataLoader $webhookDataLoader
     * @param Builder $transactionBuilder
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        private readonly WebhookDataLoader $webhookDataLoader,
        private readonly Builder $transactionBuilder,
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.refund.completed' event.
     * TODO: Implement the logic to handle the refund completed event.
     * TODO: create credit memo
     *
     * @param $webhookData
     *
     * @return void
     * @throws LocalizedException
     */
    public function processWebhook($webhookData): void
    {
        try {
            $order = $this->webhookDataLoader->loadOrderByPaymentId($webhookData['paymentId']);

            $chargeRefundTransaction = $this->transactionBuilder
                ->build(
                    $webhookData['refundId'],
                    $order,
                    [
                        'payment_id' => $webhookData['paymentId']
                    ],
                    TransactionInterface::TYPE_REFUND
                )->setParentTxnId($webhookData['paymentId']);

            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}

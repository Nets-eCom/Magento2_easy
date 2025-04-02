<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;


use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentRefundCompleted
{
    /**
     * PaymentRefundCompleted constructor.
     *
     * @param WebhookDataLoader $webhookDataLoader
     * @param Builder $transactionBuilder
     */
    public function __construct(
        private WebhookDataLoader $webhookDataLoader,
        private Builder $transactionBuilder,
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.refund.completed' event.
     *
     * @param $responseData
     * @return void
     * @throws LocalizedException
     */
    public function processWebhook($responseData)
    {
        try {
            $order = $this->webhookDataLoader->loadOrderByPaymentId($responseData['paymentId']);

            $chargeRefundTransaction = $this->transactionBuilder
                ->build(
                    $responseData['refundId'],
                    $order,
                    [
                        'payment_id' => $responseData['paymentId']
                    ],
                    TransactionInterface::TYPE_REFUND
                )->setParentTxnId($responseData['paymentId']);

            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}

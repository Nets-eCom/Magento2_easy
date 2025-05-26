<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentCancelFailed implements WebhookProcessorInterface
{
    /**
     * @param WebhookDataLoader $webhookDataLoader
     * @param Comment $comment
     */
    public function __construct(
        private readonly WebhookDataLoader $webhookDataLoader,
        private readonly Comment $comment,
    ) {
    }

    /**
     * @inheritdoc
     *
     * @throws CouldNotSaveException
     */
    public function processWebhook(array $webhookData): void
    {
        /* @var Order $order */
        $order = $this->webhookDataLoader->loadOrderByPaymentId($webhookData['data']['paymentId']);

        $this->comment->saveComment(
            __('Webhook Received. Payment cancel created for payment ID: %1', $webhookData['data']['paymentId']),
            $order
        );
    }
}

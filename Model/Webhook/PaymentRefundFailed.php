<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use NexiCheckout\Model\Webhook\WebhookInterface;

class PaymentRefundFailed implements WebhookProcessorInterface
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
     */
    public function processWebhook(WebhookInterface $webhook): void
    {
        $paymentId = $webhook->getData()->getPaymentId();
        $order = $this->webhookDataLoader->loadOrderByPaymentId($paymentId);

        $this->comment->saveComment(
            __('Webhook Received. Payment refund failed for payment ID: %1', $paymentId),
            $order
        );
    }
}

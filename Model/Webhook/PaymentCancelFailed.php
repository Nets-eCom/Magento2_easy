<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use NexiCheckout\Model\Webhook\WebhookInterface;

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
     */
    public function processWebhook(WebhookInterface $webhook): void
    {
        /* @var Order $order */
        $order = $this->webhookDataLoader->loadOrderByPaymentId($webhook->getData()->getPaymentId());

        $this->comment->saveComment(
            __('Webhook Received. Payment cancel failed for payment ID: %1', $webhook->getData()->getPaymentId()),
            $order
        );
    }
}

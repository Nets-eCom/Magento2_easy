<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

class PaymentCancelCreated implements WebhookProcessorInterface
{
    /**
     * @inheritdoc
     */
    public function processWebhook(array $webhookData): void
    {
        // TODO: Implement webhook processor logic here
    }
}

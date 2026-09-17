<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use NexiCheckout\Model\Webhook\WebhookInterface;

interface WebhookProcessorInterface
{
    /**
     * Process the webhook data.
     *
     * @param WebhookInterface $webhook
     *
     * @return void
     */
    public function processWebhook(WebhookInterface $webhook): void;
}

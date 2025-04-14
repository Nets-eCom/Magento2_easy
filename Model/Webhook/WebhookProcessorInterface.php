<?php

namespace Nexi\Checkout\Model\Webhook;

interface WebhookProcessorInterface
{
    /**
     * Process the webhook data.
     *
     * @param array $webhookData
     *
     * @return void
     */
    public function processWebhook(array $webhookData): void;
}

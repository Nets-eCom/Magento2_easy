<?php

namespace Nexi\Checkout\Model;

use Nexi\Checkout\Model\Webhook\WebhookProcessorInterface;

class WebhookHandler
{
    /**
     * @param WebhookProcessorInterface[] $webhookProcessors
     */
    public function __construct(
        private array $webhookProcessors
    ) {
    }

    /**
     * Handler passes forward on to the appropriate handler.
     *
     * @param array $webhookData
     *
     * @return void
     */
    public function handle(array $webhookData): void
    {
        $event = $webhookData['event'];
        if (array_key_exists($event, $this->webhookProcessors)) {
            $this->webhookProcessors[$event]->processWebhook($webhookData);
        }
    }

    /**
     * Get all registered webhook processors.
     *
     * @return WebhookProcessorInterface[]
     */
    public function getWebhookProcessors(): array
    {
        return $this->webhookProcessors;
    }
}

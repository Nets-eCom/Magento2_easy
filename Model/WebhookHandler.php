<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model;

use Nexi\Checkout\Model\Webhook\WebhookProcessorInterface;
use NexiCheckout\Model\Webhook\WebhookInterface;

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
     * @param WebhookInterface $webhook
     *
     * @return void
     */
    public function handle(WebhookInterface $webhook): void
    {
        $event = $webhook->getEvent()->value;
        if (array_key_exists($event, $this->webhookProcessors)) {
            $this->webhookProcessors[$event]->processWebhook($webhook);
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

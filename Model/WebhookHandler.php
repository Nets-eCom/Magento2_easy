<?php

namespace Nexi\Checkout\Model;

use Nexi\Checkout\Model\Webhook\WebhookProcessorInterface;

class WebhookHandler
{
    /**
     * WebhookHandler constructor.
     *
     * @param WebhookProcessorInterface[] $webhookProcessors
     */
    public function __construct(
        private array $webhookProcessors
    ) {
    }

    /**
     * Handler passes forward on to the appropriate handler.
     *
     * @param $webhookData
     *
     * @return void
     */
    public function handle($webhookData)
    {
        $event = $webhookData['event'];
        if (array_key_exists($event, $this->webhookProcessors)) {
            $this->webhookProcessors[$event]->processWebhook($webhookData);
        }
    }
}

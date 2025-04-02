<?php

namespace Nexi\Checkout\Gateway\Handler;

class WebhookHandler
{
    /**
     * WebhookHandler constructor.
     *
     * @param array $webhookProcessors
     */
    public function __construct(
        private array $webhookProcessors
    ) {
    }

    /**
     * Handler passes forward on to the appropriate handler.
     *
     * @param $request
     * @return void
     * @throws \Exception
     */
    public function handle($webhookData)
    {
        $event = $webhookData['event'];
        if (array_key_exists($event, $this->webhookProcessors)) {
            $this->webhookProcessors[$event]->processWebhook($webhookData);
        }
    }
}

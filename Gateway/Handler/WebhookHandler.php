<?php

namespace Nexi\Checkout\Gateway\Handler;

class WebhookHandler
{
    /**
     * WebhookHandler constructor.
     *
     * @param array $webhookHandlers
     */
    public function __construct(
        private array $webhookHandlers
    ) {
    }

    /**
     * Handler passes forward on to the appropriate handler.
     *
     * @param $response
     * @return void
     */
    public function handle($response)
    {
        $this->webhookHandlers[$response]->processWebhook($response);
    }
}

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
     * @throws \Exception
     */
    public function handle($response)
    {
        try {
            if (in_array($response['event'], $this->webhookHandlers)) {
                $this->webhookHandlers[$response['event']]->processWebhook($response['data']);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}

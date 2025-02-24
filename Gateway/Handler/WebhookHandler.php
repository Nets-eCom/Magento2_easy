<?php

namespace Nexi\Checkout\Gateway\Handler;

use NexiCheckout\Model\Webhook\EventNameEnum;

class WebhookHandler implements Ha
{

    public function __construct(
    ) {
    }

    public function handle($response)
    {
        $responseParams = $response->getParams();
        match($responseParams['event']) {
            EventNameEnum::PAYMENT_CREATED => $this->paymentCreated->process($responseParams),
            EventNameEnum::PAYMENT_RESERVATION_CREATED_V2 => $this->paymentReservationCreated->process($responseParams),
            EventNameEnum::PAYMENT_CHECKOUT_COMPLETED => $this->paymentCheckoutCompleted->process($responseParams),
            EventNameEnum::PAYMENT_CHARGE_CREATED => $this->paymentChargeCreated->process($responseParams)
        };
    }
}

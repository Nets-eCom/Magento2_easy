<?php

namespace Dibs\EasyCheckout\Controller\Webhook;

class PaymentCreated extends Webhook {

    public function execute() {

        $data = json_decode($this->request->getContent(), true);
        if (isset($data['event']) && $data['event'] == 'payment.created' && isset($data['data']['paymentId'])) {
            $this->paymentId = $data['data']['paymentId'];
            $reference = $data['data']['order']['reference'];
            $arrReference = (explode("_", $reference));
            $this->quoteId = $arrReference[2];
            PaymentCreated::startOrderCreation($this->paymentId, $this->quoteId);
        }
    }

}

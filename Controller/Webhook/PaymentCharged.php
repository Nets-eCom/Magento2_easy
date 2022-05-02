<?php

namespace Dibs\EasyCheckout\Controller\Webhook;

class PaymentCharged extends Webhook {

    /**
     * @inheritDoc
     */
    protected function beforeSave() {
        //$paymentMethod = $this->requestData['data']['paymentMethod'];
        $data = json_decode($this->request->getContent(), true);
        $additionalInformation = $this->order->getPayment()->getAdditionalInformation();
        if(isset($data['event'])) {
            if ($data['event'] == 'payment.charge.created') {
                $dibs_order_status_id = 5;
            } else if($data['event'] == 'payment.charge.created.v2') {
                $dibs_order_status_id = 6;
            }
            
            if($dibs_order_status_id > $additionalInformation['dibs_order_status_id']) {
                $additionalInformation['dibs_payment_status'] = "Charged";
                $additionalInformation['dibs_order_status_id'] = $dibs_order_status_id;
            }
            $this->order->getPayment()->setAdditionalInformation($additionalInformation);

        }
    }

}

<?php

namespace Dibs\EasyCheckout\Controller\Webhook;

class PaymentRefund extends Webhook {

    protected function beforeSave() {
        $data = json_decode($this->request->getContent(), true);
        $additionalInformation = $this->order->getPayment()->getAdditionalInformation();
        if(isset($data['event'])) {
            $dibs_order_status_id = '';
            if ($data['event'] == 'payment.refund.initiated') {
                $dibs_order_status_id = 8;
            } else if($data['event'] == 'payment.refund.initiated.v2') {
                $dibs_order_status_id = 9;
            } else if($data['event'] == 'payment.refund.completed') {
                $dibs_order_status_id = 11;
            }
            
            if ($data['event'] == 'payment.refund.initiated' || $data['event'] == 'payment.refund.initiated.v2') {
                if($dibs_order_status_id > $additionalInformation['dibs_order_status_id']) {
                    $additionalInformation['dibs_payment_status'] = "Pending Refund";
                    $additionalInformation['dibs_order_status_id'] = $dibs_order_status_id;     
                }
            } else if ($data['event'] == 'payment.refund.completed') {
                if($dibs_order_status_id > $additionalInformation['dibs_order_status_id']) {
                    $additionalInformation['dibs_payment_status'] = "Refunded";
                    $additionalInformation['dibs_order_status_id'] = $dibs_order_status_id;
                    $this->order->setStatus('closed');
                }
            }
            $this->order->getPayment()->setAdditionalInformation($additionalInformation);
        }
    }

}

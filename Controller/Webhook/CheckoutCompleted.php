<?php

namespace Dibs\EasyCheckout\Controller\Webhook;

class CheckoutCompleted extends Webhook
{
    /**
     * @inheritDoc
     */
    protected function beforeSave()
    {
        $dibs_order_status_id = 2;
        $data = json_decode($this->request->getContent(), true);
        $paymentMethod = $this->paymentMethod;
        $additionalInformation = $this->order->getPayment()->getAdditionalInformation();
        if(isset($data['event'])) {
            if($dibs_order_status_id > $additionalInformation['dibs_order_status_id']) {
                $additionalInformation['dibs_payment_status'] = "Reserved";
                $additionalInformation['dibs_payment_method'] = $paymentMethod;
                $additionalInformation['dibs_order_status_id'] = $dibs_order_status_id;
            }
            $this->order->getPayment()->setAdditionalInformation($additionalInformation);
        }
    }
}

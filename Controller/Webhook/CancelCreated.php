<?php

namespace Dibs\EasyCheckout\Controller\Webhook;

class CancelCreated extends Webhook
{
    /**
     * @inheritDoc
     */
    protected function beforeSave()
    {
        $dibs_order_status_id = 12;
        $data = json_decode($this->request->getContent(), true);
        $paymentMethod = $this->paymentMethod;
        $additionalInformation = $this->order->getPayment()->getAdditionalInformation();
        if(isset($data['event'])) {
            if($dibs_order_status_id > $additionalInformation['dibs_order_status_id']) {
                $additionalInformation['dibs_payment_status'] = "Canceled";
                $additionalInformation['dibs_payment_method'] = $paymentMethod;
                $additionalInformation['dibs_order_status_id'] = $dibs_order_status_id;
            }
            $this->order->getPayment()->setAdditionalInformation($additionalInformation);
            // $this->order->setStatus('Canceled');
            $this->order->setStatus('canceled');
        }
    }

    protected function getSuccessComment(): string
    {
        return 'Cancel created for payment ID: %s';
    }
}

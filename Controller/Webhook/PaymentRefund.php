<?php

namespace Dibs\EasyCheckout\Controller\Webhook;

use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentWebhook;

class PaymentRefund extends Webhook {

    protected function beforeSave() {
        $data = json_decode($this->request->getContent(), true);
	$paymentMethod = $this->paymentMethod;
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
	    $additionalInformation['dibs_payment_method'] = $paymentMethod;
            $this->order->getPayment()->setAdditionalInformation($additionalInformation);
        }
    }

    protected function getSuccessComment(): string
    {
        return $this->expectedEvent === CreatePaymentWebhook::EVENT_PAYMENT_REFUND_COMPLETED
            ? 'Refund completed for payment ID: %s'
            : 'Refund initiated for payment ID: %s';
    }
}

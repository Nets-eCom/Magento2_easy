<?php

namespace Dibs\EasyCheckout\Controller\Webhook;

class PaymentCharged extends Webhook {

    /**
     * @inheritDoc
     */
    protected function beforeSave() {
        $data = json_decode($this->request->getContent(), true);
	      $paymentMethod = $this->paymentMethod;
        $additionalInformation = $this->order->getPayment()->getAdditionalInformation();
        if(isset($data['event'])) {
            if ($data['event'] == 'payment.charge.created') {
                $dibs_order_status_id = 5;
            } else if($data['event'] == 'payment.charge.created.v2') {
                $dibs_order_status_id = 6;
            }

            if($dibs_order_status_id > $additionalInformation['dibs_order_status_id']) {
                $additionalInformation['dibs_payment_status'] = "Charged";
		            $additionalInformation['dibs_payment_method'] = $paymentMethod;
                $additionalInformation['dibs_order_status_id'] = $dibs_order_status_id;
            }
            $this->order->getPayment()->setAdditionalInformation($additionalInformation);

        }
    }

    protected function afterSave(): void
    {
        $responseHandler = $this->dibsCheckoutContext->getResponseHandler();
        $paymentResponse = $this->dibsCheckoutContext->getDibsOrderHandler()->loadDibsPaymentById($this->paymentId, $this->storeId);
        $responseHandler->saveOrder($paymentResponse, $this->order);
    }
}

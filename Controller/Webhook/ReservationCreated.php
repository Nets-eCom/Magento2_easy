<?php

namespace Dibs\EasyCheckout\Controller\Webhook;

class ReservationCreated extends Webhook
{
    /**
     * @inheritDoc
     */
    protected function beforeSave()
    {
        $dibs_order_status_id = 3;
        $data = json_decode($this->request->getContent(), true);
        $paymentMethod = $this->requestData['data']['paymentMethod'];
        $paymentType = $this->requestData['data']['paymentType'];
        $additionalInformation = $this->order->getPayment()->getAdditionalInformation();
        if(isset($data['event'])) {
            $additionalInformation['dibs_payment_method'] = $paymentMethod;
            $this->order->setData('dibs_payment_method', $paymentMethod);
            if($dibs_order_status_id > $additionalInformation['dibs_order_status_id']) {
                $additionalInformation['dibs_payment_status'] = "Reserved";
                $additionalInformation['dibs_order_status_id'] = $dibs_order_status_id;
            }
            $this->order->getPayment()->setAdditionalInformation($additionalInformation);
            $helper = $this->dibsCheckoutContext->getHelper();
            // we need to add invoice fee here to order if its enabled
            if ($helper->useInvoiceFee()
                && $paymentType === "INVOICE"
                && $paymentMethod === "Easy-Invoice"
            ) {
                $invoiceFee = $helper->getInvoiceFee() * 1.25; // TODO remove hardcode! - VAT is corrected later when invoice is created
                $this->order->setDibsInvoiceFee($invoiceFee);
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function afterSave()
    {
        // Send order confirmation if not sent already
        if ($this->order->getEmailSent()) {
            return;
        }

        try {
            $this->dibsCheckoutContext->getOrderSender()->send($this->order);
            $this->logInfo("Sent order confirmation");
        } catch (\Exception $e) {
            $this->logError("Error sending order confirmation email");
            $this->logError("Error message: {$e->getMessage()}");
            $this->logError("Stack trace: {$e->getPrevious()->getTraceAsString()}");
            $this->order->addCommentToStatusHistory(
                    "Callback for {$this->expectedEvent} encountered an error when trying to send order confirmation email",
                    false
            );
        }
    }

    protected function getSuccessComment(): string
    {
        return 'Reservation created for payment ID: %s';
    }
}

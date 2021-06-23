<?php

namespace Dibs\EasyCheckout\Controller\Webhook;

class ReservationCreated extends Webhook
{
    /**
     * @inheritDoc
     */
    protected function beforeSave()
    {
        $paymentMethod = $this->requestData['data']['paymentMethod'];
        $paymentType = $this->requestData['data']['paymentType'];
        $additionalInformation = $this->order->getPayment()->getAdditionalInformation();
        $additionalInformation['dibs_payment_method'] = $paymentMethod;
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

    /**
     * @inheritDoc
     */
    protected function afterSave()
    {
        $swishHandler = $this->dibsCheckoutContext->getSwishHandler();
        $paymentResponse = $this->dibsCheckoutContext->getDibsOrderHandler()->loadDibsPaymentById($this->paymentId);
        $swishValid = $swishHandler->isSwishOrderValid($paymentResponse);
        if ($swishValid) {
            $swishHandler->saveOrder($paymentResponse, $this->order);
        }
    }
}

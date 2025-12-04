<?php

namespace Dibs\EasyCheckout\Controller\Webhook;

class CheckoutCompleted extends Webhook
{
    public const sendEmailMethodsMap = [
        'SWISH' => true,
        'KLARNA' => true,
        'TRUSTLY' => true,
        'SOFORT' => true
    ];

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

                $this->order->setData('dibs_payment_method', $paymentMethod);
            }
            $this->order->getPayment()->setAdditionalInformation($additionalInformation);
        }
    }

    /**
     * @inheritDoc
     */
    protected function afterSave()
    {
        // update reference if on order submit was to early to update
        $this->dibsCheckoutContext->getDibsOrderHandler()->updatePaymentReference($this->order);

        // To sent email for Swish, Klarna, Sofort, Trustly payment method
        if ($this->shouldSendEmail()) {
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
    }

    protected function getSuccessComment(): string
    {
        return 'Checkout completed for payment ID: %s';
    }

    private function shouldSendEmail(): bool
    {
        return isset(self::sendEmailMethodsMap[strtoupper($this->paymentMethod)]) && !$this->order->getEmailSent();
    }
}

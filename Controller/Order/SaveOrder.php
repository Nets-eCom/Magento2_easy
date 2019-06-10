<?php


namespace Dibs\EasyCheckout\Controller\Order;
use Dibs\EasyCheckout\Controller\Checkout;
use Dibs\EasyCheckout\Model\Client\ClientException;

class SaveOrder extends Checkout
{
    public function execute()
    {
        /*
        if ($this->ajaxRequestAllowed()) {
            return;
        }
        */

        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);

        // todo? csrf...
        //$ctrlkey    = (string)$this->getRequest()->getParam('ctrlkey');
        $paymentId  = $this->getRequest()->getParam('pid');
        $checkoutPaymentId = $this->getCheckoutSession()->getDibsPaymentId();
        $quote = $this->getDibsCheckout()->getQuote();

        /* // Todo remove comment when stopped testing
        if (!$paymentId || !$checkoutPaymentId || ($paymentId != $checkoutPaymentId)) {
            $checkout->getLogger()->error("Invalid request");
            if (!$checkoutPaymentId) {
                $checkout->getLogger()->error("No dibs checkout payment id in session.");
            }

            if ($paymentId != $checkoutPaymentId) {
                $checkout->getLogger()->error("The received payment id does not match the one in the session.");
            }

            return false;
        }


        if (!$quote) {
            $checkout->getLogger()->error("No quote found for this customer.");
            return false;
        }

        // check other quote stuff
        */

        try {
            $payment = $checkout->getDibsPaymentHandler()->loadDibsPaymentById($paymentId);
        } catch (ClientException $e) {
            if ($e->getHttpStatusCode() == 404) {
                $checkout->getLogger()->error("The dibs payment with ID: " . $paymentId . " was not found in dibs.");
                return false;
            } else {
                $checkout->getLogger()->error("Something went wrong when we tried to fetch the payment ID from Dibs. Http Status code: " . $e->getHttpStatusCode());
                $checkout->getLogger()->error("Error message:" . $e->getMessage());
                $checkout->getLogger()->debug($e->getResponseBody());

                // todo show error to customer in magento! order could not be placed

            }

            return false;
        }

        if ($payment->getOrderDetails()->getReference() !== $checkout->getDibsPaymentHandler()->generateReferenceByQuoteId($quote->getId())) {
            $checkout->getLogger()->error("The customer Quote ID doesn't match with the dibs payment reference: " . $payment->getOrderDetails()->getReference());
            return false;
        }

        if ($payment->getSummary()->getReservedAmount() === null) {
            $checkout->getLogger()->error("Found no summary for the payment id: " . $payment->getPaymentId() . "... This must mean that they customer hasn't checkout out yet!");
            return false;
        }


        try {
            $checkout->placeOrder($payment, $quote);
        } catch (\Exception $e) {
            $checkout->getLogger()->error("Could not place order for dibs payment with payment id: " . $payment->getPaymentId() . ", Quote ID:" . $quote->getId());
            $checkout->getLogger()->error("Error message:" . $e->getMessage());

            // todo show error to customer in magento! order could not be placed
            return false;
        }


        // TODO send redirect url too success page!
    }


}
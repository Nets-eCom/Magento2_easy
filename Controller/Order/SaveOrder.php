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


        echo "<pre>";
        var_dump($payment);
        die;

    }


}
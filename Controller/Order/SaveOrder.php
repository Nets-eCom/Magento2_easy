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
        $session = $this->getCheckoutSession();

        $checkoutPaymentId = $session->getDibsPaymentId();
        $quote = $this->getDibsCheckout()->getQuote();

        if (!$quote) {
            return $this->respondWithError("Your session has expired. Quote missing.");
        }

        // Todo remove comment when stopped testing
        if (!$paymentId || !$checkoutPaymentId || ($paymentId != $checkoutPaymentId)) {
            $checkout->getLogger()->error("Invalid request");
            if (!$checkoutPaymentId) {
                $checkout->getLogger()->error("No dibs checkout payment id in session.");
                return $this->respondWithError("Your session has expired.");

            }

            if ($paymentId != $checkoutPaymentId) {
                return $checkout->getLogger()->error("The session has expired or is wrong.");
            }

            return $checkout->getLogger()->error("Invalid data.");
        }



        try {
            $payment = $checkout->getDibsPaymentHandler()->loadDibsPaymentById($paymentId);
        } catch (ClientException $e) {
            if ($e->getHttpStatusCode() == 404) {
                $checkout->getLogger()->error("The dibs payment with ID: " . $paymentId . " was not found in dibs.");
                return $this->respondWithError("Could not create an order. The payment was not found in dibs.");
            } else {
                $checkout->getLogger()->error("Something went wrong when we tried to fetch the payment ID from Dibs. Http Status code: " . $e->getHttpStatusCode());
                $checkout->getLogger()->error("Error message:" . $e->getMessage());
                $checkout->getLogger()->debug($e->getResponseBody());

                return $this->respondWithError("Could not create an order, please contact site admin. Dibs seems to be down!");
            }
        }

        if ($payment->getOrderDetails()->getReference() !== $checkout->getDibsPaymentHandler()->generateReferenceByQuoteId($quote->getId())) {
            $checkout->getLogger()->error("The customer Quote ID doesn't match with the dibs payment reference: " . $payment->getOrderDetails()->getReference());
            return $this->respondWithError("Could not create an order. Invalid data. Contact admin.");
        }

        if ($payment->getSummary()->getReservedAmount() === null) {
            $checkout->getLogger()->error("Found no summary for the payment id: " . $payment->getPaymentId() . "... This must mean that they customer hasn't checked out yet!");
            return $this->respondWithError("We could not create your order... The payment hasn't reached Dibs. Payment id: " . $payment->getPaymentId());
        }


        try {
            $order = $checkout->placeOrder($payment, $quote);
        } catch (\Exception $e) {
            $checkout->getLogger()->error("Could not place order for dibs payment with payment id: " . $payment->getPaymentId() . ", Quote ID:" . $quote->getId());
            $checkout->getLogger()->error("Error message:" . $e->getMessage());

           return $this->respondWithError("We could not create your order. Please contact the site admin with this error and payment id: " . $payment->getPaymentId());
        }


        // TODO send redirect url too success page!



        // clear old sessions
        $session->clearHelperData();
        $session->clearQuote()->clearStorage();


        // we set new sessions
        $session
            ->setLastQuoteId($order->getQuoteId())
            ->setLastSuccessQuoteId($order->getQuoteId())
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());


        $this->getResponse()->setBody(json_encode(
            array(
                'redirectTo' => $this->dibsCheckoutContext->getHelper()->getSuccessPageUrl()
            )
        ));
        return false;
    }


    protected function respondWithError($message,$redirectTo = false, $extraData = [])
    {
        $data = array('messages' => $message, "redirectTo" => $redirectTo);
        $data = array_merge($data, $extraData);
        $this->getResponse()->setBody(json_encode($data));
        return false;
    }

}
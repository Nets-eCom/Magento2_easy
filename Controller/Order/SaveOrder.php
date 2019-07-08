<?php


namespace Dibs\EasyCheckout\Controller\Order;
use Dibs\EasyCheckout\Controller\Checkout;
use Dibs\EasyCheckout\Model\CheckoutException;

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

        try {
            $orderPlaced = $checkout->tryToSaveDibsPayment($paymentId);
        } catch (CheckoutException $e) {
            return $this->respondWithError($e->getMessage());
        } catch (\Exception $e) {
            $checkout->getLogger()->error($e->getMessage());
            return $this->respondWithError("Something went wrong.");
        }

        if ($orderPlaced) {
            $this->getResponse()->setBody(json_encode(
                array(
                    'redirectTo' => $this->dibsCheckoutContext->getHelper()->getSuccessPageUrl()
                )
            ));
        } else {
            return $this->respondWithError("Unknown error that should not appear!");

        }

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
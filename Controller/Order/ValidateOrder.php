<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Model\Client\ClientException;

class ValidateOrder extends Update {

    /**
     * Validate order before sending to Nets
     *
     * @inheridoc
     */
    public function execute() {
        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);

        $checkoutPaymentId = $this->getCheckoutSession()->getDibsPaymentId();
        $quote = $this->getDibsCheckout()->getQuote();

        if (!$checkoutPaymentId) {
            $checkout->getLogger()->error("Validate Order: Found no dibs payment ID.");
            return $this->respondWithError("Your session has expired, found no dibs payment id.");
        }

        if (!$quote) {
            $checkout->getLogger()->error("Validate Order: No quote found for this customer.");
            return $this->respondWithError("Your session has expired, found no quote.");
        }

        try {
            $storeId = $this->quote->getStoreId();
            $payment = $checkout->getDibsPaymentHandler()->loadDibsPaymentById($checkoutPaymentId, $storeId);
        } catch (ClientException $e) {
            if ($e->getHttpStatusCode() == 404) {
                $checkout->getLogger()->error("Validate Order: The dibs payment with ID: " . $checkoutPaymentId . " was not found in dibs.");
                return $this->respondWithError("Found no Dibs Order for this session. Please refresh the site or clear your cookies.");
            } else {
                $checkout->getLogger()->error("Validate Order: Something went wrong when we tried to fetch the payment ID from Dibs. Http Status code: " . $e->getHttpStatusCode());
                $checkout->getLogger()->error("Validate Order: Error message:" . $e->getMessage());
                $checkout->getLogger()->debug($e->getResponseBody());

                return $this->respondWithError("Something went wrong when we tried to retrieve the order from Dibs. Please try again or contact an admin.");
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                    $e,
                    __('Something went wrong.')
            );

            $checkout->getLogger()->error("Validate Order: Something went wrong. Might have been the request parser. Payment ID: " . $checkoutPaymentId . "... Error message:" . $e->getMessage());
            return $this->respondWithError("Something went wrong... Contact site admin.");
        }

        if (!$quote->isVirtual() && $payment->getConsumer()->getShippingAddress() === null) {
            $checkout->getLogger()->error("Validate Order: Consumer has no shipping address.");
            return $this->respondWithError("Please add shipping information.");
        }

        try {
            if (!$quote->isVirtual() && !$quote->getShippingAddress()->getShippingMethod()) {
                $checkout->getLogger()->error("Validate Order: Consumer has not choosen a shipping method.");
                return $this->respondWithError("Please choose a shipping method.");
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                    $e,
                    __('Something went wrong.')
            );

            $checkout->getLogger()->error("Validate Order: Something went wrong... Payment ID: " . $checkoutPaymentId . "... Error message:" . $e->getMessage());
            return $this->respondWithError("Something went wrong... Contact site admin.");
        }

        $this->dibsCheckout->getHelper()->lockQuoteSignature($quote);
        $this->getResponse()->setBody(json_encode(['chooseShippingMethod' => false, 'error' => false]));
        return false;
    }

    protected function respondWithError($message, $chooseShippingMethod = false, $extraData = []) {
        $data = ['messages' => $message, "chooseShippingMethod" => $chooseShippingMethod, 'error' => true];
        $data = array_merge($data, $extraData);
        $this->getResponse()->setBody(json_encode($data));
        return false;
    }

}

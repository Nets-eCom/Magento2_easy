<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Controller\Checkout;
use Dibs\EasyCheckout\Model\CheckoutException;
use Dibs\EasyCheckout\Model\Client\ClientException;
use Dibs\EasyCheckout\Model\Client\DTO\GetPaymentResponse;
use Magento\Quote\Model\Quote;
use Magento\Framework\App\ResponseInterface;

class SaveOrder extends Checkout
{
    /**
     * @var array
     */
    private $validationResult = [];

    /**
     * @var GetPaymentResponse
     */
    private $dibsPayment;

    /**
     * @var string
     */
    private $paymentId;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @inheridoc
     */
    public function execute()
    {
        $this->paymentId = $this->getRequest()->getPostValue('pid', false);
        if (! $this->paymentId) {
            return $this->respondWithError('Invalid payment id');
        }

        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);

        $this->validateOrder();

        if ($this->validationResult['error']) {
            return $this->respondWithError($this->validationResult['message']);
        }

        $order = $this->validationResult['order'];
        if ($order === false) {
            try {
                $order = $this->dibsCheckout->placeOrder($this->dibsPayment, $this->quote);
            } catch (\Exception $e) {
                $this->respondWithError(
                    "An error occurred when attempting to save the order. You can try to place your order again. If the problem persists, contact a site admin.",
                );
            }
        }

        $this->dibsCheckout->saveDibsPayment($this->paymentId, $order);

        return $this->respondWithPaymentId($this->paymentId);
    }

    /**
     * @param $paymentId
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    private function respondWithPaymentId($paymentId)
    {
        $response = $this->getResponse();
        $response->setBody(\json_encode([
            'paymentId' => $paymentId
        ]));

        return $response;
    }

    /**
     * @param $message
     * @param false $redirectTo
     * @param array $extraData
     *
     * @return ResponseInterface
     */
    protected function respondWithError($message, $redirectTo = false, $extraData = [])
    {
        $data = ['messages' => __($message), "redirectTo" => $redirectTo, 'error' => true];
        $data = array_merge($data, $extraData);
        $this->getResponse()->setBody(json_encode($data));
        return $this->getResponse();
    }

    /**
     * @return void
     */
    private function validateOrder()
    {
        $this->validationResult = [
            'error' => false,
            'message' => '',
            'order' => false
        ];

        $checkout = $this->getDibsCheckout();

        $checkoutPaymentId = $this->paymentId;
        $this->quote = $this->getDibsCheckout()->getQuote();

        $order = $this->dibsCheckoutContext->loadPendingOrder($checkoutPaymentId);

        // Prevent multiple order creation - use existing pending_payment order as base for payment completion
        if ($order->getId()) {
            $this->validationResult['order'] = $order;
            return;
        }

        if (!$this->quote->getId()) {
            $checkout->getLogger()->error("Validate Order: Payment ID {$checkoutPaymentId}: No quote found for this customer.");
            $this->validationResult = [
                'error' => true,
                'message' => 'Your session has expired, found no quote id.'
            ];
            return;
        }

        try {
            $this->dibsPayment = $checkout->getDibsPaymentHandler()->loadDibsPaymentById($checkoutPaymentId);
        } catch (ClientException $e) {
            $this->validationResult['error'] = true;
            if ($e->getHttpStatusCode() == 404) {
                $checkout->getLogger()->error("Validate Order: The dibs payment with ID: " . $checkoutPaymentId . " was not found in dibs.");
                $this->validationResult['message'] = 'Found no Dibs Order for this session. Please refresh the site or clear your cookies.';
                return;
            } else {
                $checkout->getLogger()->error("Validate Order: Something went wrong when we tried to fetch the payment ID from Dibs. Http Status code: " . $e->getHttpStatusCode());
                $checkout->getLogger()->error("Validate Order: Error message:" . $e->getMessage());
                $checkout->getLogger()->debug($e->getResponseBody());
                $this->validationResult['message'] = 'Something went wrong when we tried to retrieve the order from Dibs. Please try again or contact an admin.';
                return;
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong.')
            );

            $checkout->getLogger()->error("Validate Order: Something went wrong. Might have been the request parser. Payment ID: " . $checkoutPaymentId . "... Error message:" . $e->getMessage());
            $this->validationResult = [
                'error' => true,
                'message' => 'Something went wrong... Contact site admin.'
            ];
            return;
        }

        if (!$this->quote->isVirtual() && $this->dibsPayment->getConsumer()->getShippingAddress() === null) {
            $checkout->getLogger()->error("Validate Order: Payment ID {$checkoutPaymentId}: Consumer has no shipping address.");
            $this->validationResult = [
                'error' => true,
                'message' => 'Please add shipping information.'
            ];
            return;
        }

        try {
            if (!$this->quote->isVirtual() && !$this->quote->getShippingAddress()->getShippingMethod()) {
                $checkout->getLogger()->error("Validate Order: Payment ID {$checkoutPaymentId}: Consumer has not choosen a shipping method.");
                $this->validationResult = [
                    'error' => true,
                    'message' => 'Please choose a shipping method.'
                ];
                return;
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong.')
            );

            $checkout->getLogger()->error("Validate Order: Something went wrong... Payment ID: " . $checkoutPaymentId . "... Error message:" . $e->getMessage());
            $this->validationResult = [
                'error' => true,
                'message' => 'Something went wrong... Contact site admin.'
            ];
            return;
        }
    }
}

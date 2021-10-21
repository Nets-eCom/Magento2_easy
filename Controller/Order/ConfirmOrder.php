<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Controller\Checkout;
use Magento\Framework\App\ResponseInterface;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;
use Magento\Sales\Model\Order;

class ConfirmOrder extends Checkout
{
    /**
     * @var string
     */
    private $paymentId;

    /**
     * @var string
     */
    private $hostedPaymentId;

    /**
     * @var Order
     */
    private $order;

    public function execute()
    {
        $this->paymentId = $this->getRequest()->getPostValue('pid', false);

        // Hosted will send payment ID as a get param
        $this->hostedPaymentId = $this->getRequest()->getParam('paymentid', false);
        if ($this->hostedPaymentId) {
            $params = array('pid' => $this->hostedPaymentId);
            $this->hostedFlowOrder($this->hostedPaymentId);
            return $this->handleHostedRequest();
        }

        $this->getDibsCheckout()->setCheckoutContext($this->dibsCheckoutContext);
        $this->order = $this->dibsCheckoutContext->getOrderFactory()->create();
        $this->dibsCheckoutContext->getOrderResourceFactory()->create()->load(
            $this->order,
            $this->paymentId,
            'dibs_payment_id'
        );

        // No order found? This should never happen, but let's log the error just in case.
        if (!$this->order->getId()) {
            $this->dibsCheckout->getLogger()->critical(
                "[ConfirmOrder][{$this->paymentId}]No order found after checkout!"
            );
            return $this->respondWithError(
                "We are sorry, but your order seems to have gone missing. "
                . "Please contact customer support with Nets Payment ID: " . $this->paymentId
            );
        }

        $this->paymentId = $this->order->getDibsPaymentId();
        $this->addSuccessCommentToOrder();

        try {
            $this->dibsCheckoutContext->getOrderRepository()->save($this->order);
        } catch (\Exception $e) {
            // Just log this, we get another chance with the webhook callback
            $this->dibsCheckout->getLogger()->critical("[ConfirmOrder][{$this->paymentId}]Error when saving order!");
            $this->dibsCheckout->getLogger()->critical(
                "[ConfirmOrder][{$this->paymentId}]Exception: " . $e->getMessage(),
                $e->getTrace()
            );
        }

        $this->clearQuote();
        return $this->respondWithSuccessRedirect();
    }

    /**
     * Handle reqest after payment completed in Hosted integration
     *
     * @return ResponseInterface
     */
    private function handleHostedRequest()
    {
        $this->getDibsCheckout()->setCheckoutContext($this->dibsCheckoutContext);
        $this->order = $this->dibsCheckoutContext->getOrderFactory()->create();
        $this->dibsCheckoutContext->getOrderResourceFactory()->create()->load(
            $this->order,
            $this->hostedPaymentId,
            'dibs_payment_id'
        );

        $helper = $this->dibsCheckoutContext->getHelper();

        // No order found? This should never happen, but let's log the error just in case.
        if (!$this->order->getId()) {
            $this->dibsCheckout->getLogger()->critical(
                "[ConfirmOrder][{$this->paymentId}]No order found after checkout!"
            );
            $message =
                "We are sorry, but your order seems to have gone missing. "
                . "Please contact customer support with Nets Payment ID: " . $this->hostedPaymentId;
            $this->messageManager->addErrorMessage(__($message));
            return $this->_redirect('checkout/cart');
        }
        $this->addSuccessCommentToOrder();
        try {
            $this->dibsCheckoutContext->getOrderRepository()->save($this->order);
        } catch (\Exception $e) {
            $this->dibsCheckout->getLogger()->critical("[ConfirmOrder][{$this->paymentId}]Error when saving order!");
            $this->dibsCheckout->getLogger()->critical(
                "[ConfirmOrder][{$this->paymentId}]Exception: " . $e->getMessage(),
                $e->getTrace()
            );
        }
        $this->clearQuote();
        return $this->_redirect($helper->getCheckoutUrl('success'));
    }

    /**
     * @param $message
     * @param false $redirectTo
     * @param array $extraData
     *
     * @return ResponseInterface
     */
    private function respondWithError($message, $redirectTo = false, $extraData = [])
    {
        $data = ['messages' => __($message), "redirectTo" => $redirectTo, 'error' => true];
        $data = array_merge($data, $extraData);
        return $this->getResponse()->setBody(json_encode($data));
    }

    /**
     * @return ResponseInterface
     */
    private function respondWithSuccessRedirect()
    {
        $redirectTo = $this->dibsCheckoutContext->getHelper()->getCheckoutUrl('success');
        $data = ['messages' => '', "redirectTo" => $redirectTo, 'error' => false];
        return $this->getResponse()->setBody(json_encode($data));
    }

    /**
     * @return void
     */
    private function clearQuote()
    {
        $checkoutSession = $this->checkoutSession;
        $checkoutSession->clearHelperData();
        $checkoutSession->unsDibsPaymentId();

        $order = $this->order;
        $checkoutSession
            ->clearQuote()
            ->clearStorage()
            ->setLastQuoteId($order->getQuoteId())
            ->setLastSuccessQuoteId($order->getQuoteId())
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());
    }

    /**
     * Adds info comment to order, changes state to Processing if necessary
     *
     * @return void
     */
    private function addSuccessCommentToOrder()
    {
        if(!$this->paymentId){
            $this->paymentId = $this->hostedPaymentId;
        }
        $comment = "Nets Easy Checkout completed for payment ID: " . $this->paymentId;
        if ($this->order->getState() === Order::STATE_PENDING_PAYMENT) {
            $this->order->setState(Order::STATE_PROCESSING);
            $status = $this->dibsCheckoutContext->getHelper()->getProcessingOrderStatus($this->order->getStore());
            $this->order->addCommentToStatusHistory($comment, $status);
            return;
        }

        $this->order->addCommentToStatusHistory($comment, false);
    }

    private function hostedFlowOrder( $paymentId )
    {
        if (! $paymentId) {
            return $this->respondWithError('Invalid payment id');
        }

        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);

        $this->validateOrder($paymentId);

        if ($this->validationResult['error']) {
            return $this->respondWithError($this->validationResult['message']);
        }

        $order = $this->validationResult['order'];
        if ($order === false) {
            try {
                $order = $this->dibsCheckout->placeOrder($this->dibsPayment, $this->quote);
            } catch (\Exception $e) {
                return $this->respondWithError(
                    "An error occurred when we tried to save your order. Please make sure all required fields are filled and try again. If the problem persists, contact customer support."
                );
            }
        } 

        $this->dibsCheckout->saveDibsPayment($paymentId, $order);
        return $this->respondWithPaymentId($paymentId);
    }

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
  /*  protected function respondWithError($message, $redirectTo = false, $extraData = [])
    {
        $data = ['messages' => __($message), "redirectTo" => $redirectTo, 'error' => true];
        $data = array_merge($data, $extraData);
        $this->getResponse()->setBody(json_encode($data));
        return $this->getResponse();
    }*/

    /**
     * @return void
     */
    private function validateOrder($paymentId)
    {
        $this->validationResult = [
            'error' => false,
            'message' => '',
            'order' => false
        ];

        $checkout = $this->getDibsCheckout();

        $checkoutPaymentId = $paymentId;
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

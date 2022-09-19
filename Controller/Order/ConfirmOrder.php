<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Controller\Checkout;
use Magento\Framework\App\ResponseInterface;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;
use Magento\Sales\Model\Order;

class ConfirmOrder extends Checkout {

    /**
     * @var string
     */
    private $paymentId;

    /**
     * @var string
     */
    private $hostedPaymentId;

    /**
     * @var string
     */
    private $embeddedPaymentId;

    /**
     * @var Order
     */
    private $order;

    public function execute() {
        $this->paymentId = $this->getRequest()->getPostValue('pid', false);

        //Trustly and Sofort will send payment Id as a get param
        $this->embeddedPaymentId = $this->getRequest()->getParam('paymentId', false);
        if ($this->embeddedPaymentId) {
            $this->paymentId = $this->embeddedPaymentId;
        }

        // Hosted will send payment ID as a get param
        $this->hostedPaymentId = $this->getRequest()->getParam('paymentid', false);

        if ($this->hostedPaymentId) {
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

        //Trustly and Sofort will redirect to success and other will ajax response send
        if ($this->embeddedPaymentId) {
            $helper = $this->dibsCheckoutContext->getHelper();
            return $this->_redirect($helper->getCheckoutUrl('success'));
        } else {
            return $this->respondWithSuccessRedirect();
        }
    }

    /**
     * Handle reqest after payment completed in Hosted integration
     *
     * @return ResponseInterface
     */
    private function handleHostedRequest() {
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
            $message = "We are sorry, but your order seems to have gone missing. "
                    . "Please contact customer support with Nets Payment ID: " . $this->hostedPaymentId;
            $this->messageManager->addErrorMessage(__($message));
            return $this->_redirect('checkout/cart');
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
    private function respondWithError($message, $redirectTo = false, $extraData = []) {
        $data = ['messages' => __($message), "redirectTo" => $redirectTo, 'error' => true];
        $data = array_merge($data, $extraData);
        return $this->getResponse()->setBody(json_encode($data));
    }

    /**
     * @return ResponseInterface
     */
    private function respondWithSuccessRedirect() {
        $redirectTo = $this->dibsCheckoutContext->getHelper()->getCheckoutUrl('success');
        $data = ['messages' => '', "redirectTo" => $redirectTo, 'error' => false];
        return $this->getResponse()->setBody(json_encode($data));
    }

    /**
     * @return void
     */
    private function clearQuote() {
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
    private function addSuccessCommentToOrder() {
        $comment = "Nets Easy Checkout completed for payment ID: " . $this->paymentId;
        if ($this->order->getState() === Order::STATE_PENDING_PAYMENT) {
            $this->order->setState(Order::STATE_PROCESSING);
            $status = $this->dibsCheckoutContext->getHelper()->getProcessingOrderStatus($this->order->getStore());
            $this->order->addCommentToStatusHistory($comment, $status);
            return;
        }

        $this->order->addCommentToStatusHistory($comment, false);
    }

}

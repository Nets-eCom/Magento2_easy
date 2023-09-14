<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Controller\Checkout;
use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Model\Order;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Dibs\EasyCheckout\Model\Checkout as DibsCheckout;
use Dibs\EasyCheckout\Model\CheckoutContext as DibsCheckoutContext;
use Magento\Checkout\Model\Cart;
use Dibs\EasyCheckout\Model\Client\DTO\CancelPayment;
use Dibs\EasyCheckout\Model\Client\Api\Payment;

class ConfirmOrder extends Checkout {

    private \Dibs\EasyCheckout\Helper\Data $helper;
    private \Magento\Checkout\Model\Cart $cart;
    private \Dibs\EasyCheckout\Model\Client\Api\Payment $payment;

    /**
     * @var string
     */
    private $paymentId;

    /**
     * @var string
     */
    private $hostedPaymentId;

    private ?Order $order = null;

    /**
     * @inheridoc
     */
    public function __construct(
            \Magento\Framework\App\Action\Context $context,
            \Magento\Customer\Model\Session $session,
            \Dibs\EasyCheckout\Helper\Data $helper,
            CustomerRepositoryInterface $customerRepository,
            AccountManagementInterface $accountManagement,
            \Magento\Checkout\Model\Session $checkoutSession,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory,
            DibsCheckout $dibsCheckout,
            DibsCheckoutContext $dibsCheckoutContext,
            Cart $cart,
            Payment $payment
    ) {
        $this->helper = $helper;
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
	    $this->cart = $cart;
        $this->payment = $payment;

        parent::__construct(
                $context,
                $session,
                $customerRepository,
                $accountManagement,
                $checkoutSession,
                $storeManager,
                $resultPageFactory,
                $dibsCheckout,
                $dibsCheckoutContext
        );
    }

    public function execute() {
        $this->logInfo("in confirm order");
        if ($this->helper->getCheckoutFlow() == "Vanilla") {
            $trustFlag = false;
            $paymentFailed = false;
            $this->paymentId = $this->getRequest()->getPostValue('pid', false);
            if (empty($this->paymentId)) {
                $trustFlag = true;
                $paymentFailed = $this->getRequest()->getParam('paymentFailed', false);
                $this->paymentId = $this->getRequest()->getParam('paymentId', false);
                if ($paymentFailed) {
                    $message = "We are sorry, order has been cancelled by user on checkout.";
                    $this->messageManager->addErrorMessage(__($message));
                    $this->dibsCheckout->getLogger()->error($message . " for paymentId " . $this->paymentId);
                    return $this->_redirect('checkout/cart');
                }
            }
        }

        // Hosted will send payment ID as a get param
        if ($this->helper->getCheckoutFlow() == "HostedPaymentPage") {
            $this->paymentId = $this->hostedPaymentId = $this->getRequest()->getParam('paymentid', false);
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
        //$this->addSuccessCommentToOrder();

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
        //$helper = $this->dibsCheckoutContext->getHelper();
        if ($this->helper->getCheckoutFlow() == "Vanilla") {
            $this->logInfo("in Vanilla");
            if ($trustFlag) {
                $this->logInfo("in Trust : ".$this->helper->getCheckoutUrl('success'));
                return $this->_redirect($this->helper->getCheckoutUrl('success'));
            } else {
                $this->logInfo("Else Trust");
                return $this->respondWithSuccessRedirect();
            }
        }
        if ($this->helper->getCheckoutFlow() == "HostedPaymentPage") {
            return $this->_redirect($this->helper->getCheckoutUrl('success'));
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
        if ($this->order->getId()) {
            $this->clearQuote();
            return $this->_redirect($helper->getCheckoutUrl('success'));
        } else{

            $this->dibsCheckout->getLogger()->critical("[ConfirmOrder][{$this->paymentId}]No order found after checkout!");
            $quote = $this->cart->getQuote();
            $jsonErrorData = $quote->getErrorMessage();
            $arrayErrorData = json_decode($jsonErrorData, true);
            if(isset($arrayErrorData['error']) && !empty($arrayErrorData['error'])){
                $paymentObj = new CancelPayment();
                $cancelAmount = (int) round($quote->getBaseGrandTotal() * 100, 0);
                $paymentObj->setAmount($cancelAmount);
            
                $this->logInfo("canceled the tranasaction");
            
                $this->payment->cancelPayment($paymentObj, $this->hostedPaymentId);

                $errorMessage = $arrayErrorData['message'];
                $message = "Could not create order, we have canceled your transaction. Error is: " . $errorMessage . ". Please contact customer support with Nets Payment ID: " . $this->hostedPaymentId ;

                $this->messageManager->addErrorMessage(__($message));
                return $this->_redirect('checkout/cart');
            }
            
            header("Refresh:0");
        }
        
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
        $comment = "Nets Easy Checkout inititate for payment ID: " . $this->paymentId;
        if ($this->order->getState() === Order::STATE_PENDING_PAYMENT) {
            $this->order->setState(Order::STATE_PROCESSING);
            $status = $this->dibsCheckoutContext->getHelper()->getProcessingOrderStatus($this->order->getStore());
            $this->order->addCommentToStatusHistory($comment, $status);
            return;
        }

        $this->order->addCommentToStatusHistory($comment, false);
    }
    
    protected function logInfo($message) {
        $this->dibsCheckoutContext->getLogger()->info($message);
    }

}

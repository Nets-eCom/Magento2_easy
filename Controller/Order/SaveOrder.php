<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Controller\Checkout;
use Dibs\EasyCheckout\Helper\Data;
use Dibs\EasyCheckout\Model\CheckoutException;
use Dibs\EasyCheckout\Model\Client\ClientException;
use Dibs\EasyCheckout\Model\Client\DTO\GetPaymentResponse;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;
use Dibs\EasyCheckout\Model\Checkout as DibsCheckout;
use Dibs\EasyCheckout\Model\CheckoutContext as DibsCheckoutContext;
use Magento\Quote\Model\Quote;
use Magento\Framework\App\ResponseInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\QuoteFactory;

class SaveOrder extends Checkout {

    private Data $helper;
    private RequestInterface $request;
    private QuoteFactory $quoteFactory;

    private array $validationResult = [];

    private ?GetPaymentResponse $dibsPayment = null;

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
    public function __construct(
            \Magento\Framework\App\Action\Context $context,
            \Magento\Customer\Model\Session $session,
            Data $helper,
            CustomerRepositoryInterface $customerRepository,
            AccountManagementInterface $accountManagement,
            \Magento\Checkout\Model\Session $checkoutSession,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory,
            DibsCheckout $dibsCheckout,
            DibsCheckoutContext $dibsCheckoutContext,
            RequestInterface $request,
            JsonFactory $resultFactory,
            QuoteFactory $quoteFactory
    ) {
        $this->helper = $helper;
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->quoteFactory = $quoteFactory;

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

        $this->logInfo("in saveorder to create order");

        $checkout = $this->getDibsCheckout();
        $paymentCheckout = new CreatePaymentCheckout();

        if ($this->helper->getCheckoutFlow() == "HostedPaymentPage") {
            $this->paymentId = $this->getRequest()->getParam('paymentId', false);
            $this->logInfo("pid for hosted from webhook ".$this->paymentId);
            $this->logInfo("data is  ".$this->request->getContent());

            $data = json_decode($this->request->getContent(), true);
            $this->paymentId = $data['data']['paymentId'];
            $this->logInfo("pid for hosted from webhook1 ".$this->paymentId);
            $this->paymentId = $data['data']['paymentId'];
            $reference = $data['data']['order']['reference'];
            $arrReference = (explode("_", $reference));
            $quoteId = $arrReference[2];
            $this->logInfo("qid for hosted from webhook1 ".$quoteId);
        } elseif ("Vanilla" == $this->helper->getCheckoutFlow()) {

            $this->paymentId = $this->getRequest()->getPostValue('pid', false);
        }


        if (!$this->paymentId) {
            return $this->respondWithError('Invalid payment id1');
        }

        $checkout->setCheckoutContext($this->dibsCheckoutContext);

        if ($this->helper->getCheckoutFlow() == "HostedPaymentPage") {

            $this->validateOrder($quoteId);
            $this->logInfo("webhook validate order");
            //$this->validateOrder();
        } elseif ("Vanilla" == $this->helper->getCheckoutFlow()) {

            $this->logInfo("validating quote before creating order");

            $this->validateOrder();
        }

        if ($this->validationResult['error']) {

            $this->logInfo("error in validating quote, error is: " . $this->validationResult['message']);

            return $this->respondWithError($this->validationResult['message']);
        }

        $order = $this->validationResult['order'];
        if ($order === false) {
            try {
                $this->logInfo("validation successful, creating order");
                $order = $this->dibsCheckout->placeOrder($this->dibsPayment, $this->quote);
                $this->logInfo("order created");

            } catch (\Exception $e) {
                if ("HostedPaymentPage" == $this->helper->getCheckoutFlow()) {
                    $errorData = array("error"=>true,"message"=>$e->getMessage());
                    $this->quote->setErrorMessage(json_encode($errorData));
                    $this->quote->setDibsPaymentId(NULL);
                    $this->quote->save();
                }
                
                $this->logInfo("could not create order, error is: " . $e->getMessage());
                return $this->respondWithError("Could not create order, error is: " . $e->getMessage());
            }
        }

        $this->dibsCheckout->saveDibsPayment($this->paymentId, $order);

        //$this->respondWithPaymentId($this->paymentId);

        $result = $this->resultFactory->create('json');
        $result->setData([
                'status' => 'Success.',
                'paymentId' => $this->paymentId
            ]);
        $result->setHttpResponseCode(200);
        return $result;
    }

    /**
     * @param $paymentId
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    private function respondWithPaymentId($paymentId) {
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
    protected function respondWithError($message, $redirectTo = false, $extraData = []) {
        $data = ['messages' => __($message), "redirectTo" => $redirectTo, 'error' => true];
        $data = array_merge($data, $extraData);
        $this->getResponse()->setBody(json_encode($data));
        return $this->getResponse();
    }

    /**
     * @return void
     */
    private function validateOrder($quoteId = '') {
        $this->validationResult = [
            'error' => false,
            'message' => '',
            'order' => false
        ];

        $checkout = $this->getDibsCheckout();
        $checkoutPaymentId = $this->paymentId;
        $this->quote = $this->getDibsCheckout()->getQuote();

        if ("HostedPaymentPage" == $this->helper->getCheckoutFlow()) {

            $quote = $this->quoteFactory->create()->load($quoteId);
            $quoteId = $quote->getId();
            $this->quote = $quote;
            //$this->quote = $this->getDibsCheckout()->getQuote();
        } elseif ("Vanilla" == $this->helper->getCheckoutFlow()) {

            $this->quote = $this->getDibsCheckout()->getQuote();
        }

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
            $storeId = $this->quote->getStoreId();
            $this->dibsPayment = $checkout->getDibsPaymentHandler()->loadDibsPaymentById($checkoutPaymentId, $storeId);
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

    public function createOrder($id, $quoteId) {
        $checkout = $this->getDibsCheckout();
        $this->paymentId = $id; //$this->getRequest()->getPostValue('pid', false);
        if (!$this->paymentId) {
            return $this->respondWithError('Invalid payment id');
        }

        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);

        $this->validateOrder($quoteId);

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

        $this->dibsCheckout->saveDibsPayment($this->paymentId, $order);

        return $this->respondWithPaymentId($this->paymentId);
        return true;
    }

    protected function logInfo($message) {
        $this->dibsCheckoutContext->getLogger()->info($message);
    }

}

<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Api\CheckoutFlow;
use Dibs\EasyCheckout\Controller\Checkout;
use Dibs\EasyCheckout\Helper\Data;
use Dibs\EasyCheckout\Model\Checkout as DibsCheckout;
use Dibs\EasyCheckout\Model\CheckoutContext as DibsCheckoutContext;
use Magento\Framework\App\ResponseInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;

class EmbeddedSaveOrder extends Checkout {

    private Data $helper;

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
            Json $json
    ) {
        $this->helper = $helper;

        parent::__construct(
                $context,
                $session,
                $customerRepository,
                $accountManagement,
                $checkoutSession,
                $storeManager,
                $resultPageFactory,
                $dibsCheckout,
                $dibsCheckoutContext,
                $json
        );
    }

    public function execute() {

        if (CheckoutFlow::FLOW_VANILLA !== $this->helper->getCheckoutFlow()) {
            return $this->respondWithError('Invalid checkout flow');
        }

        $quote = $this->dibsCheckout->getQuote();
        $lastOrderStatus = $this->dibsCheckout->getCheckout()->setLastOrderStatus();
        if (!$quote->getId() && $lastOrderStatus === Order::STATE_PENDING_PAYMENT) {
            // quote might be empty if payment failed first time and customer tries again
            $result = $this->resultFactory->create('json');
            $result->setData([
                'status' => 'Empty quote.'
            ]);
            $result->setHttpResponseCode(200);

            return $result;
        }

        if (!$quote->getId()) {
            return $this->respondWithError('Empty quote');
        }
        
        $this->logInfo("in EmbeddedSaveOrder to create order");
        
        if (!$this->validateQuoteSignature()) {
            $this->logInfo("validation of quote signature failed");
            
            return;
        }
        
        $paymentId = $this->getRequest()->getPostValue('pid', null);
        if (!$paymentId) {
            return $this->respondWithError('Invalid payment id');
        }
        
        $this->dibsCheckout->checkCart();
        
        try {
            $this->logInfo("validation successful, creating order");
            $dibsPayment = $this->dibsCheckout->getDibsPaymentHandler()->loadDibsPaymentById($paymentId, $quote->getStoreId());
            $order = $this->dibsCheckout->placeOrder($dibsPayment, $quote);
            $this->logInfo("order created");

        } catch (\Exception $e) {
            $this->logInfo("could not create order, error is: " . $e->getMessage());

            return $this->respondWithError("Could not create order, error is: " . $e->getMessage());
        }

        $this->dibsCheckout->saveDibsPayment($paymentId, $order);

        $result = $this->resultFactory->create('json');
        $result->setData([
                'status' => 'Success.',
                'paymentId' => $paymentId
            ]);
        $result->setHttpResponseCode(200);

        return $result;
    }

    protected function respondWithError(string $message): ResponseInterface {
        $data = ['messages' => __($message), 'error' => true];
        $response = $this->getResponse();
        $response->setBody(json_encode($data));
        $response->setHttpResponseCode(400);

        return $response;
    }

    protected function logInfo(string $message): void {
        $this->dibsCheckoutContext->getLogger()->info($message);
    }
}

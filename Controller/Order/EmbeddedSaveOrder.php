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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class EmbeddedSaveOrder extends Checkout {

    private Data $helper;

    private OrderRepository $orderRepository;
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
            Json $json,
            OrderRepository $orderRepository
    ) {
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;

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

        $paymentId = $this->getRequest()->getPostValue('pid', null);
        if (!$paymentId) {
            return $this->respondWithError('Invalid payment id');
        }

        $quote = $this->dibsCheckout->getQuote();
        $lastOrderStatus = $this->dibsCheckout->getCheckout()->getLastOrderStatus();
        if (!$quote->getId() && $lastOrderStatus === Order::STATE_PENDING_PAYMENT && $this->getLastOrderPaymentId() === $paymentId)  {
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

        $this->dibsCheckout->checkCart();

        try {
            $this->logInfo("validation successful, creating order");
            $dibsPayment = $this->dibsCheckout->getDibsPaymentHandler()->loadDibsPaymentById($paymentId, $quote->getStoreId());
            $order = $this->dibsCheckout->placeOrder($dibsPayment, $quote);
            $this->dibsCheckout->getCheckout()->setLastOrderStatus($order->getStatus());
            $this->dibsCheckout->getCheckout()->setLastOrderId($order->getId());
            $this->logInfo("order created");

        } catch (\Exception $e) {
            $this->logInfo("could not create order, error is: " . $e->getMessage());

            return $this->respondWithError("Could not create order, error is: " . $e->getMessage());
        }

        $this->dibsCheckout->saveDibsPayment($paymentId, $order);

        $result = $this->resultFactory->create('json');
        $result->setData([
                'status' => 'Success.',
                'paymentId' => $paymentId,
                'reload' => 0,
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

    private function getLastOrderPaymentId(): ?string {
        $lastOrderId = $this->dibsCheckout->getCheckout()->getLastOrderId();
        try {
            $lastOrder = $this->orderRepository->get($lastOrderId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $lastOrder->getDibsPaymentId();
    }
}

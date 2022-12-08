<?php

namespace Dibs\EasyCheckout\Controller\Webhook;

use Dibs\EasyCheckout\Model\Checkout as DibsCheckout;
use Dibs\EasyCheckout\Model\CheckoutContext as DibsCheckoutContext;
use Dibs\EasyCheckout\Model\Client\DTO\UpdatePaymentReference;
use Dibs\EasyCheckout\Controller\Order\SaveOrder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;

abstract class Webhook implements HttpPostActionInterface, CsrfAwareActionInterface {
    // Object properties

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var DibsCheckout
     */
    protected $dibsCheckout;

    /**
     * @var DibsCheckoutContext
     */
    protected $dibsCheckoutContext;

    /**
     * @var JsonFactory
     */
    protected $resultFactory;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $paymentRepo;
    //
    // di.xml scalar properties

    /**
     * @var string
     */
    protected $logPrefix;

    /**
     * @var string
     */
    protected $expectedEvent;

    /**
     * @var string
     */
    protected $successComment;

    /**
     * @var bool
     */
    protected $sendEmail;
    //
    // Processing properties

    /**
     * @var string
     */
    protected $paymentId;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var array
     */
    protected $requestData;

    /**
     * @var string
     */
    protected $paymentMethod;

    /**
     * @var string
     */
    protected $storeId;

    /**
     * @var bool
     */
    protected $authorized;

    //

    public function __construct(
            \Dibs\EasyCheckout\Helper\Data $helper,
            \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi,
            SaveOrder $saveOrder,
            RequestInterface $request,
            DibsCheckout $dibsCheckout,
            DibsCheckoutContext $dibsCheckoutContext,
            JsonFactory $resultFactory,
            OrderPaymentRepositoryInterface $paymentRepo,
            string $expectedEvent,
            string $successComment,
            bool $sendEmail
    ) {
        $this->helper = $helper;
        $this->paymentApi = $paymentApi;
        $this->saveOrder = $saveOrder;
        $this->request = $request;
        $this->dibsCheckout = $dibsCheckout;
        $this->dibsCheckoutContext = $dibsCheckoutContext;
        $this->resultFactory = $resultFactory;
        $this->paymentRepo = $paymentRepo;
        $this->expectedEvent = $expectedEvent;
        $this->successComment = $successComment;
        $this->sendEmail = $sendEmail;
    }

    public function execute() {
        $result = $this->resultFactory->create();
        $result->setData([]);

        // Validate authorization if Csrf validation hasn't activated
        if (!isset($this->authorized)) {
            $ourSecret = $this->dibsCheckoutContext->getHelper()->getWebhookSecret();
            $this->authorized = ($ourSecret && $ourSecret === $this->request->getHeader("Authorization"));
            if (!$this->authorized) {
                $this->logError("Process - Invalid authorization");
                $result->setHttpResponseCode(401);
                return $result;
            }
            $this->logInfo("Process - Valid authorization");
        }

        $data = json_decode($this->request->getContent(), true);
        $this->logInfo("Response Received : " . $this->request->getContent());
        $this->logInfo("Event : " . $data['event'] . " == " . $this->expectedEvent);
        if (!isset($data['event']) || !isset($data['data']['paymentId'])) {
            $result->setHttpResponseCode(401);
            return $result;
        }

        $this->paymentId = $data['data']['paymentId'];
        $this->requestData = $data;
        $this->logInfo("Starting order update process");

        // Load order
        $this->order = $this->dibsCheckoutContext->getOrderFactory()->create();
        $this->dibsCheckoutContext->getOrderResourceFactory()->create()->load(
                $this->order,
                $this->paymentId,
                'dibs_payment_id'
        );

        if (!$this->order->getId()) {
            $this->logInfo("Order does not exist yet. Webhook will retry.");
            $result->setHttpResponseCode(404);
            return $result;
        }

        $this->storeId = $this->order->getStoreId();
        $paymentDetails = $this->paymentApi->getPayment($this->paymentId, $this->storeId);
        $this->logInfo("Fetch Payment Method : " . $paymentDetails->getPaymentDetails()->getPaymentMethod());
        $this->paymentMethod = $paymentDetails->getPaymentDetails()->getPaymentMethod();

        $this->beforeSave();

        try {
            $this->addSuccessCommentToOrder();
            $this->dibsCheckoutContext->getOrderRepository()->save($this->order);
            $this->paymentRepo->save($this->order->getPayment());

            $this->logInfo("Order is updated successfully");
            $orderId = $this->order->getIncrementId();
            $payment = $this->order->getPayment();
            if ($payment->getMethod() == "dibseasycheckout") {
                $this->logInfo('from the webhook ' . $this->order->getIncrementId());
                // To sent email for Swish payment method
                if (strtoupper($this->paymentMethod) == 'SWISH' && $data['event'] == 'payment.checkout.completed') {
                    try {
                        $this->dibsCheckoutContext->getOrderSender()->send($this->order);
                        $this->logInfo("Sent order confirmation");
                    } catch (\Exception $e) {
                        $this->logError("Error sending order confirmation email");
                        $this->logError("Error message: {$e->getMessage()}");
                        $this->logError("Stack trace: {$e->getPrevious()->getTraceAsString()}");
                        $this->order->addCommentToStatusHistory(
                                "Callback for {$this->expectedEvent} encountered an error when trying to send order confirmation email",
                                false
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logError("Could not update order");
            $this->logError("Error message: {$e->getMessage()}");
            $this->logError("Stack trace: {$e->getPrevious()->getTraceAsString()}");
            $this->order->addCommentToStatusHistory(
                    "Callback for {$this->expectedEvent} failed to update order. Check easycheckout log file for details",
                    false
            );
            $result->setHttpResponseCode(500);
            return $result;
        }

        // Send order confirmation if not sent already
        if ($this->sendEmail && !$this->order->getEmailSent()) {
            try {
                $this->dibsCheckoutContext->getOrderSender()->send($this->order);
                $this->logInfo("Sent order confirmation");
            } catch (\Exception $e) {
                $this->logError("Error sending order confirmation email");
                $this->logError("Error message: {$e->getMessage()}");
                $this->logError("Stack trace: {$e->getPrevious()->getTraceAsString()}");
                $this->order->addCommentToStatusHistory(
                        "Callback for {$this->expectedEvent} encountered an error when trying to send order confirmation email",
                        false
                );
            }
        }

        $this->afterSave();
        $result->setHttpResponseCode(200);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool {
        // Validate authorization
        $ourSecret = $this->dibsCheckoutContext->getHelper()->getWebhookSecret();
        $this->authorized = ($ourSecret && $ourSecret === $request->getHeader("Authorization"));
        if (!$this->authorized) {
            $this->logError("Csrf - Invalid authorization");
            return $this->authorized;
        }
        $this->logInfo("Csrf - Valid authorization");
        return $this->authorized;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException {
        return null;
    }

    /**
     * Implement in child classes. Runs before order save.
     *
     * @return void
     */
    protected function beforeSave() {
        return;
    }

    /**
     * As above, but after save instead
     *
     * @return void
     */
    protected function afterSave() {
        return;
    }

    /**
     * Log an informative message
     *
     * @param string $message
     * @return void
     */
    protected function logInfo($message) {
        $this->dibsCheckoutContext->getLogger()->info($this->getLogPrefix() . $message);
    }

    /**
     * Log an error message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError($message, $context = []) {
        $this->dibsCheckoutContext->getLogger()->error($this->getLogPrefix() . $message, $context);
    }

    /**
     * Construct prefix for log messages
     *
     * @return string
     */
    protected function getLogPrefix() {
        return "[Webhook:{$this->expectedEvent}][{$this->paymentId}]";
    }

    /**
     * Adds info comment to order, changes state to Processing if necessary
     *
     * @return void
     */
    protected function addSuccessCommentToOrder() {
        $comment = sprintf($this->successComment, $this->paymentId);
        if ($this->order->getState() === Order::STATE_PENDING_PAYMENT) {
            $this->order->setState(Order::STATE_PROCESSING);
            $status = $this->dibsCheckoutContext->getHelper()->getProcessingOrderStatus($this->order->getStore());
            $this->order->addCommentToStatusHistory($comment, $status);
            return;
        }

        $this->order->addCommentToStatusHistory($comment, false);
    }

    protected function startOrderCreation($paymentId, $quoteId) {

        $this->saveOrder->createOrder($paymentId, $quoteId);
    }

}

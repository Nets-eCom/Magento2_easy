<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Controller\Checkout;
use Dibs\EasyCheckout\Logger\Logger;
use Dibs\EasyCheckout\Model\Checkout as DibsCheckout;
use Dibs\EasyCheckout\Model\CheckoutContext as DibsCheckoutCOntext;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentWebhook;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\Quote;

class WebhookCallback extends Checkout
{
    /** @var Logger */
    protected $logger;

    /** @var \Magento\Quote\Model\QuoteFactory $quoteFactory */
    protected $quoteFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * WebhookCallback constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $accountManagement
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     * @param DibsCheckout $dibsCheckout
     * @param DibsCheckoutCOntext $dibsCheckoutContext
     * @param Logger $logger
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        DibsCheckout $dibsCheckout,
        DibsCheckoutCOntext $dibsCheckoutContext,
        Logger $logger,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    ) {
        $this->logger = $logger;
        $this->quoteFactory = $quoteFactory;
        $this->jsonResultFactory = $jsonResultFactory;

        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement,
            $checkoutSession,
            $storeManager,
            $resultPageFactory,
            $dibsCheckout,
            $dibsCheckoutContext
        );
    }

    /**
     * @inheridoc
     */
    public function execute()
    {
        $quoteId = $this->getRequest()->getParam('qid');
        $data = json_decode($this->getRequest()->getContent(), true);
        $result = $this->jsonResultFactory->create();
        $result->setData([]);

        if (!isset($data['event']) || $data['event'] !== CreatePaymentWebhook::EVENT_PAYMENT_CHECKOUT_COMPLETED || !isset($data['data']['paymentId'])) {
            $result->setHttpResponseCode(200);
            return $result;
        }

        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);

        // Validate authorization
        $ourSecret = $checkout->getHelper()->getWebhookSecret();
        if ($ourSecret && $ourSecret != $this->getRequest()->getHeader("Authorization")) {
            $result->setHttpResponseCode(401);
            return $result;
        }

        try {
            $quote = $this->loadQuote($quoteId);
        } catch (\Exception $e) {
            $this->logger->error($e);

            // maybe magento is down?
            $result->setHttpResponseCode(500);
            return $result;
        }

        try {
            $paymentId = $data['data']['paymentId'];
            $dibsPayment = $checkout->getDibsPaymentHandler()->loadDibsPaymentById($paymentId);
        } catch (\Exception $e) {
            $this->logger->error("[Webhook][{$paymentId}] Could not load dibs payment for quote (id: {$quoteId})");
            $this->logger->error("[Webhook][{$paymentId}] Exception message: {$e->getMessage()}");

            // maybe nets is down
            $result->setHttpResponseCode(500);
            return $result;
        }

        $newSignature = $this->dibsCheckoutContext->getHelper()->generateHashSignatureByQuote($quote);
        if ($newSignature !== $quote->getHashSignature()) {
            $this->logger->error("[Webhook][{$paymentId}] Quote signature doesn't match. Probably customer cart was changed.");
            $result->setHttpResponseCode(400);
            return $result;
        }

        $this->logger->info("[Webhook][{$paymentId}] Quote signature is verified.");
        // we check that its the correct quote
        if ($checkout->getDibsPaymentHandler()->generateReferenceByQuoteId($quoteId) !== $dibsPayment->getOrderDetails()->getReference()) {
            // either its wrong, or order has been placed already! (since we update reference when order is placed to magento order id)
            $result->setHttpResponseCode(200);
            return $result;
        }

        $weHandleConsumerData = false;
        $changeUrl = true;
        if ($this->dibsCheckoutContext->getHelper()->getCheckoutUrl() !== $dibsPayment->getCheckoutUrl()) {
            $weHandleConsumerData = true;
            $changeUrl = false;
        }

        // HOWERE if quote is virtual, we let them handle consumer data, since we dont add these fields in our checkout!
        if ($quote->isVirtual()) {
            $weHandleConsumerData = false;
        }

        // OK the payment exists, payment ids are matching... lets check no order has been placed
        $orderCollection = $this->dibsCheckoutContext->getOrderCollectionFactory()->create();
        $ordersCount = $orderCollection
            ->addFieldToFilter('dibs_payment_id', ['eq' => $paymentId])
            ->load()
            ->count();

        if ($ordersCount > 0) {
            $this->dibsCheckoutContext->getLogger()->error("[Webhook][{$paymentId}] Order is already created");
            $result->setHttpResponseCode(200);
            return $result;
        }

        try {
            $order = $checkout->placeOrder($dibsPayment, $quote, $weHandleConsumerData, false);
            $this->dibsCheckoutContext->getLogger()->info("[Webhook][{$paymentId}] Order is created successfuly");
        } catch (\Exception $e) {
            $this->logger->error("[Webhook][{$paymentId}] Could not place order for dibs payment Quote id: {$quote->getId()}" );
            $this->logger->error("[Webhook][{$paymentId}] Error message: {$e->getMessage()}");

            $result->setHttpResponseCode(500);
            return $result;
        }

        try {
            $checkout->getDibsPaymentHandler()->updateMagentoPaymentReference($order, $paymentId, $changeUrl);
        } catch (\Exception $e) {
            $this->logger->error("[Webhook][{$paymentId}] Order created with ID: {$order->getIncrementId()}");
            // But we could not update reference ID at dibs. Please handle it manually, it has id: quote_id_: "
            $this->logger->error("[Webhook][{$paymentId}] But we could not update reference ID at Dibs. 
            Please handle it manually, it has id: quote_id: {$quote->getId()}");

            // Lets ignore this and save it in logs! let customer see his/her order confirmation!
            $this->logger->error("[Webhook][{$paymentId}] Error message: {$e->getMessage()}");
        }
        $this->logger->info("[Webhook][{$paymentId}] Updated payment reference. Finished transaction");

        $result->setHttpResponseCode(200);
        return $result;
    }


    /**
     * @param $quoteId
     * @return Quote
     * @throws \Exception
     */
    protected function loadQuoteById($quoteId)
    {
        return $this->quoteFactory->create()->loadByIdWithoutStore($quoteId);
    }


    /**
     * @param $quoteId int
     * @return Quote|void
     * @throws \Exception
     */
    protected function loadQuote($quoteId)
    {
        try {
            $quote = $this->loadQuoteById($quoteId);
        } catch (\Exception $e) {
            $this->logger->error("Webhook: We found no quote for this Nets Payment.");
            throw new \Exception("Found no quote object for this Nets Payment ID.");
        }

        return $quote;
    }
}

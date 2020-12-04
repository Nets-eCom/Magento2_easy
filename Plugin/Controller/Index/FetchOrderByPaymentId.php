<?php

namespace Dibs\EasyCheckout\Plugin\Controller\Index;

use Dibs\EasyCheckout\Controller\Index\Index;
use Dibs\EasyCheckout\Model\CheckoutContext;
use Magento\Checkout\Controller\Action;
use Magento\Checkout\Model\Session;

class FetchOrderByPaymentId
{
    /**
     * @var CheckoutContext
     */
    private $checkoutContext;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * FetchOrderByPaymentId constructor.
     *
     * @param CheckoutContext $checkoutContext
     * @param Session $checkoutSession
     */
    public function __construct(
        CheckoutContext $checkoutContext,
        Session $checkoutSession
    ) {
        $this->checkoutContext = $checkoutContext;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param Index $action
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function aroundExecute(Index $action, callable $execute)
    {
        $paymentId = $this->getPaymentId($action);
        if ($action->getRequest()->getParam('paymentFailed')) {
            $action->getMessageManager()->addErrorMessage(__('The payment was canceled or failed.'));
            $this->logMessage("[Index][{$paymentId}] The payment was canceled or failed", true);
            return $action->redirect('*/*');
        }

        return $paymentId ? $this->fetchOrderByPaymentId($action, $paymentId) : $execute();
    }

    /**
     * @param Index $action
     * @param $paymentId
     *
     * @return mixed
     */
    private function fetchOrderByPaymentId(Index $action, $paymentId)
    {
        $helper = $this->checkoutContext->getHelper();
        $this->logMessage("[Index][$paymentId] Waiting for an order to be created from webhook.");
        $handleTimeout = $helper->getWebhookHandleTimeout() ?: 40;

        for ($sleepCounter = 1; $sleepCounter < $handleTimeout; $sleepCounter++) {
            if ($order = $this->fetchOrderByPid($paymentId)) {
                $this->logMessage("[Index][{$paymentId}] Found order successfuly. Redirecting to " . $helper->getSuccessPageUrl());
                $this->clearQuote($order);

                return $action->redirect($helper->getSuccessPageUrl());
            }
            $this->logMessage("[Index][{$paymentId}] Orders not found, sleeping for 1s.");
            sleep(1);
        }

        $action->getMessageManager()->addErrorMessage(__('We cannot verify you payment on Nets side, timeout is reached. Your payment ID is %1', $paymentId));
        $this->logMessage("[Webhook][{$paymentId}] Timeout is reached after {$handleTimeout} seconds.", true);

        return $action->redirect('checkout/cart');
    }

    /**
     * @param Action $action
     *
     * @return mixed
     */
    private function getPaymentId(Action $action)
    {
        return $action->getRequest()->getParam('paymentId') ?: $action->getRequest()->getParam('paymentid');
    }

    /**
     * @param $message
     * @param false $isError
     */
    private function logMessage($message, $isError = false)
    {
        $logger = $this->checkoutContext->getLogger();
        $isError ? $logger->error($message) : $logger->info($message);
    }

    /**
     * @param $paymentId
     *
     * @return \Magento\Sales\Model\Order
     */
    private function fetchOrderByPid($paymentId) : ?\Magento\Sales\Model\Order
    {
        $orderCollection = $this->checkoutContext->getOrderCollectionFactory()->create();
        $ordersCollection = $orderCollection
            ->addFieldToFilter('dibs_payment_id', ['eq' => $paymentId])
            ->load();

        return $ordersCollection->count() ? $ordersCollection->getFirstItem() : null;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    private function clearQuote(\Magento\Sales\Model\Order $order)
    {
        $checkoutSession = $this->checkoutSession;
        $checkoutSession->clearHelperData();

        $checkoutSession
            ->clearQuote()
            ->clearStorage()
            ->setLastQuoteId($order->getQuoteId())
            ->setLastSuccessQuoteId($order->getQuoteId())
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());
    }
}

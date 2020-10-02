<?php
namespace Dibs\EasyCheckout\Controller\Index;

use Dibs\EasyCheckout\Controller\Checkout;
use Dibs\EasyCheckout\Model\CheckoutException;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;

class Index extends Checkout
{
    const URL_CHECKOUT_CART_PATH = 'checkout/cart';
    const URL_CHECKOUT_PATH      = 'checkout';

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page|void
     * @throws CheckoutException
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('paymentFailed')) {
            throw new CheckoutException(__("The payment was canceled or failed."), '*/*');
        }

        // Fetching order id after redirect
        if ($paymentId = $this->getPaymentId()) {
            return $this->fetchOrderByPaymentId($paymentId);
        }

        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);

        $integrationType = $this->getDibsCheckout()->getHelper()->getCheckoutFlow();
        $useHostedCheckout = $integrationType === CreatePaymentCheckout::INTEGRATION_TYPE_HOSTED;

        if ($integrationType === CreatePaymentCheckout::INTEGRATION_TYPE_OVERLAY) {
            $integrationType = CreatePaymentCheckout::INTEGRATION_TYPE_HOSTED;
            $useHostedCheckout = true;
            $isOverlayType = true;
        } else {
            $isOverlayType = false;
        }

        $dibsPayment = null;
        try {
            $checkout->initCheckout(false);
            $dibsPayment = $checkout->initDibsCheckout($integrationType);
        } catch (CheckoutException $e) {
            if ($e->isReload()) {
                $this->messageManager->addNoticeMessage($e->getMessage());
            } else {
                $this->messageManager->addErrorMessage($e->getMessage());
            }

            if ($e->getRedirect()) {
                if ($useHostedCheckout) {
                    $this->_redirect(self::URL_CHECKOUT_CART_PATH);
                } else {
                    $this->_redirect($e->getRedirect());
                }
                return;
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage() ? $e->getMessage() : __('Cannot initialize Nets Easy Checkout (%1)', get_class($e)));
            $checkout->getLogger()->error("[" . __METHOD__ . "] (" . get_class($e) . ") {$e->getMessage()} ");
            $checkout->getLogger()->critical($e);

            $this->_redirect(self::URL_CHECKOUT_PATH);
            return;
        }

        $useIframe = $integrationType === CreatePaymentCheckout::INTEGRATION_TYPE_EMBEDDED; // the embedded type is used with iframe
        $locale = $checkout->getLocale();
        $checkoutUrl = '';
        if ($dibsPayment) {
            // used for hosted integration type
            $checkoutUrl = $dibsPayment->getCheckoutUrl();

            // make sure its not OUR checkout url, this will be the case when we use embedded flow
            if ($checkoutUrl == $this->getDibsCheckout()->getHelper()->getCheckoutUrl()) {
                $checkoutUrl = '';
            }
            if ($checkoutUrl) {
                $checkoutUrl .= "&language=" . $locale;
            }

            $unsetPayment = false;
            // THIS might happen if the store owner changes integration flow, when a customer already started with the old flow!, for embedded
            if (!$checkoutUrl && !$useIframe) {
                $checkout->getLogger()->error("Cannot initialize Nets Easy Checkout! Hosted flow chosen but no checkout URL is returned from Dibs.");
                $unsetPayment = true;
            }

            // THIS might also happen when store owner changes integration flow, when a customer already started the old flow, but for hosted
            if ($useIframe && $checkoutUrl) {
                $checkout->getLogger()->error("Cannot initialize Nets Easy Checkout! Embedded flow chosen but checkout URL is returned from Dibs.");
                $unsetPayment = true;
            }

            // we need to try again
            if ($unsetPayment) {
                $this->messageManager->addNoticeMessage(__('We had to restart the checkout flow.'));
                $this->getCheckoutSession()->unsDibsPaymentId(); // unset this payment id, it wont work anymore!

                if ($useHostedCheckout) {
                    $this->_redirect(self::URL_CHECKOUT_CART_PATH);
                } else {
                    $this->_redirect('*'); // reload the page
                }
                return;
            }
        }

        $redirectToHosted = false;
        if ($useHostedCheckout) {
            $redirectToHosted = true;

            $q = $this->getDibsCheckout()->getQuote();

            if (!$q->isVirtual() && $q->getShippingAddress() && !$q->getShippingAddress()->getShippingMethod()) {
                if ($isOverlayType) {
                    $this->_redirect(self::URL_CHECKOUT_PATH);
                    return;
                } else {
                    $this->messageManager->addNoticeMessage(__('You need to choose a shipping method.'));
                    $this->_redirect(self::URL_CHECKOUT_CART_PATH);
                    return;
                }
            }
        }

        if ($redirectToHosted && $checkoutUrl) {
            // here we redirect to the hosted payment gateway, this only happens when ?checkRedirect param is used
            // this param is set in the default magento checkout, when nets is chosen. $redirectToHosted is only true
            // if hosted (redirect flow or overlay) is enabled in settings)
            $this->_redirect($checkoutUrl);
            return;
        }

        // if we reached here and use hosted checkout is on, redirect to standard checkout!
        if ($useHostedCheckout) {
            $this->_redirect(self::URL_CHECKOUT_CART_PATH);
            return;
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Nets Easy Checkout'));

        // set variables we depend on in the block
        /** @var \Dibs\EasyCheckout\Block\Checkout $block */
        $block = $resultPage->getLayout()->getBlock('dibs_easy_checkout.dibs');
        $block->setDibsLocale($locale)
            ->setUseIframe($useIframe)
            ->setCheckoutRedirectUrl($checkoutUrl);

        /** @var \Dibs\EasyCheckout\Block\Checkout $block */
        $block = $resultPage->getLayout()->getBlock('dibs_easy_checkout.to_payment');
        $block->setDibsLocale($locale)
            ->setUseIframe($useIframe)
            ->setCheckoutRedirectUrl($checkoutUrl);

        return $resultPage;
    }

    /**
     * @param $paymentId
     *
     * @return \Magento\Framework\App\ResponseInterface
     * @throws CheckoutException
     */
    private function fetchOrderByPaymentId($paymentId)
    {
        $this->dibsCheckoutContext->getLogger()->info("[Index][$paymentId] Waiting for an order to be created from webhook.");
        $handleTimeout = $this->dibsCheckoutContext->getHelper()->getWebhookHandleTimeout() ?: 40;

        for ($sleepCounter = 1; $sleepCounter < $handleTimeout; $sleepCounter++) {
            $orderCollection = $this->dibsCheckoutContext->getOrderCollectionFactory()->create();
            $ordersCollection = $orderCollection
                ->addFieldToFilter('dibs_payment_id', ['eq' => $paymentId])
                ->load();

            if ($ordersCollection->count()) {
                $this->dibsCheckoutContext->getLogger()->info("[Index][{$paymentId}] Order found!  Redirecting to " . $this->dibsCheckoutContext->getHelper()->getSuccessPageUrl());
                $session =  $this->getCheckoutSession();
                $session->clearHelperData();
                $session->clearQuote()->clearStorage();

                $order = $ordersCollection->getFirstItem();
                $session
                    ->setLastQuoteId($order->getQuoteId())
                    ->setLastSuccessQuoteId($order->getQuoteId())
                    ->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());

                return $this->_redirect($this->dibsCheckoutContext->getHelper()->getSuccessPageUrl());
            }
            $this->dibsCheckoutContext->getLogger()->info('[Index] Orders not found. Sleep 1s. ' . $paymentId);
            sleep(1);
        }

        $this->messageManager->addErrorMessage(__('We cannot verify you payment on Nets side, timeout is reached. Your payment ID is %1', $paymentId));
        $this->dibsCheckoutContext->getLogger()->error("[Webhook][{$paymentId}] Timeout is reached after {$handleTimeout} seconds.");

        return $this->_redirect(self::URL_CHECKOUT_CART_PATH);
    }

    /**
     * @return mixed
     */
    public function getPaymentId()
    {
        return $this->getRequest()->getParam('paymentId') ?: $this->getRequest()->getParam('paymentid');
    }
}

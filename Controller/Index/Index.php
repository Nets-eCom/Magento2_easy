<?php
namespace Dibs\EasyCheckout\Controller\Index;

use Dibs\EasyCheckout\Controller\Checkout;
use Dibs\EasyCheckout\Model\CheckoutException;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;

class Index extends Checkout
{

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page|void
     */
    public function execute()
    {
        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);

        $integrationType = $this->getDibsCheckout()->getHelper()->getCheckoutFlow();

        // if hosted flow is used, OR if customer pays with card and is redirected, they will be sent back here, and we will try to place the order
        // dibs seems to send back both these parameters, so we test them both...
        $paymentId = $this->getRequest()->getParam("paymentId");
        if (!$paymentId) {
            $paymentId = $this->getRequest()->getParam("paymentid");
        }

        // if the customer has payed with card and is redirected back here
        if ($paymentId) {
            try {
                $orderSaved = $this->checkIfOrderShouldBeSaved($paymentId);
                if ($orderSaved) {
                    // redirect to thank you!
                    return $this->_redirect($checkout->getHelper()->getSuccessPageUrl());
                }
            } catch (CheckoutException $e) {
                if ($e->isReload()) {
                    $this->messageManager->addNoticeMessage($e->getMessage());
                } else {
                    $this->messageManager->addErrorMessage($e->getMessage());
                }

                $this->_redirect($e->getRedirect());
                return;
            }
        }

        // if not... :)
        $dibsPayment = null;
        try {
            $checkout->initCheckout(false); // magento business logic
            $dibsPayment = $checkout->initDibsCheckout($integrationType); // handles magento and DIBS business logic
        } catch (CheckoutException $e) {
            if ($e->isReload()) {
                $this->messageManager->addNoticeMessage($e->getMessage());
            } else {
                $this->messageManager->addErrorMessage($e->getMessage());
            }

            if ($e->getRedirect()) {
                $this->_redirect($e->getRedirect());
                return;
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage() ? $e->getMessage() : __('Cannot initialize Nets Easy Checkout (%1)', get_class($e)));
            $checkout->getLogger()->error("[" . __METHOD__ . "] (" . get_class($e) . ") {$e->getMessage()} ");
            $checkout->getLogger()->critical($e);

            $this->_redirect('checkout/cart');
            return;
        }

        $useIframe = $integrationType === CreatePaymentCheckout::INTEGRATION_TYPE_EMBEDDED; // the embedded type is used with iframe
        $locale = $checkout->getLocale();
        $checkoutUrl = "";
        if ($dibsPayment) {
            // used for hosted integration type
            $checkoutUrl = $dibsPayment->getCheckoutUrl();

            // make sure its not OUR checkout url, this will be the case when we use embedded flow
            if ($checkoutUrl == $this->getDibsCheckout()->getHelper()->getCheckoutUrl()) {
                $checkoutUrl = "";
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
                $this->_redirect('*'); // reload the page
                return;
            }
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
     * @return true|void
     * @throws CheckoutException
     */
    protected function checkIfOrderShouldBeSaved($paymentId)
    {
        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);

        if ($this->getRequest()->getParam('paymentFailed')) {
            throw new CheckoutException(__("The payment was canceled or failed."), '*/*');
        }

        // it will validate the payment id and everything before trying to place the order
        return $checkout->tryToSaveDibsPayment($paymentId);
    }
}

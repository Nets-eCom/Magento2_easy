<?php
namespace Dibs\EasyCheckout\Controller\Index;

use Dibs\EasyCheckout\Controller\Checkout;
use Dibs\EasyCheckout\Model\CheckoutException;


class Index extends Checkout
{


    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page|void
     */
    public function execute()
    {

        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);


        // if the customer has payed with card and is redirected back here
        if ($paymentId = $this->getRequest()->getParam("paymentId")) {

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
        try {
            $checkout->initCheckout(false); // magento business logic
            $checkout->initDibsCheckout(); // handles magento and DIBS business logic
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
            $this->messageManager->addErrorMessage($e->getMessage() ? $e->getMessage() : $this->__('Cannot initialize Dibs Easy Checkout (%1)', get_class($e)));
            //$this->getLogger()->error("[" . __METHOD__ . "] (" . get_class($e) . ") {$e->getMessage()} ");
            //$this->getLogger()->critical($e);
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Dibs Easy Checkout'));
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
            throw new CheckoutException(__("The payment was canceled or failed."),'*/*');
        }

        // it will validate the payment id and everything before trying to place the order
        return $checkout->tryToSaveDibsPayment($paymentId);
    }
}
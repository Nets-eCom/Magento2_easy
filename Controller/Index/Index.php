<?php
namespace Dibs\EasyCheckout\Controller\Index;

use Dibs\EasyCheckout\Controller\Checkout;
use Dibs\EasyCheckout\Model\CheckoutException;


class Index extends Checkout
{


    public function execute()
    {

        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);
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

}
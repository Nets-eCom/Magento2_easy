<?php
namespace Dibs\EasyCheckout\Controller\Order;

class Success extends \Dibs\EasyCheckout\Controller\Checkout
{
    /**
     * @inheridoc
     */
    public function execute()
    {
        $session = $this->getCheckoutSession();
        if (!$this->_objectManager->get('Magento\Checkout\Model\Session\SuccessValidator')->isValid()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        $lastOrderId = $session->getLastOrderId();

        $session->clearQuote(); //destroy quote, unset QuoteId && LastSuccessQuoteId
        $session->unsDibsPaymentId(); // destroy session

        // need to be BEFORE event dispach (GA need to have layout loaded, to set the orderIds on the block)
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__("Nets Easy Checkout - Success"));

        $this->_eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            ['order_ids' => [$lastOrderId]]
        );

        return $resultPage;
    }

}

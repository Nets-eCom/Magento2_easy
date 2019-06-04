<?php


namespace Dibs\EasyCheckout\Controller;


use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

use Dibs\EasyCheckout\Model\Checkout as DibsCheckout;
use Dibs\EasyCheckout\Model\CheckoutContext as DibsCheckoutCOntext;
use Magento\Checkout\Controller\Action;

abstract class Checkout extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /** @var DibsCheckout $dibsCheckout */
    protected $dibsCheckout;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /** @var DibsCheckoutCOntext $dibsCheckoutContext */
    protected $dibsCheckoutContext;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        DibsCheckout $dibsCheckout,
        DibsCheckoutCOntext $dibsCheckoutContext

    ) {
        $this->dibsCheckout = $dibsCheckout;
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->dibsCheckoutContext = $dibsCheckoutContext;

        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement
        );
    }

    /**
     * @return DibsCheckout
     */
    public function getDibsCheckout()
    {
        return $this->dibsCheckout;
    }

    protected function getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    /**
     * Validate ajax request and redirect on failure
     *
     * @return bool
     */
    protected function ajaxRequestAllowed()
    {


        if(!$this->getRequest()->isXmlHttpRequest()) {
            return false;
        }

        /* // Todo
        //check if quote was changed
        $ctrlkey    = (string)$this->getRequest()->getParam('ctrlkey');
        if(!$ctrlkey) {
            return false; //do not check
        }

        //check if cart was updated
        $currkey    = $this->getDibsCheckout()->getQuoteSignature();
        if($currkey != $ctrlkey) {
            $response = array(
                'reload'   => 1,
                'messages' =>(string)__('The cart was updated (from another location), reloading the checkout, please wait...')
            );
            $this->getCheckoutSession()->addError($this->__('The requested changes were not applied. The cart was updated (from another location), please review the cart.'));
            $this->getResponse()->setBody(Zend_Json::encode($response));
            return true;
        }
        */

        return false;
    }
}
<?php


namespace Dibs\EasyCheckout\Controller;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Dibs\EasyCheckout\Model\Checkout as DibsCheckout;
use Dibs\EasyCheckout\Model\CheckoutContext as DibsCheckoutContext;
use Magento\Checkout\Controller\Action;
use Magento\Framework\Serialize\Serializer\Json;

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

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;


    /** @var DibsCheckoutContext $dibsCheckoutContext */
    protected $dibsCheckoutContext;

    private Json $json;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        DibsCheckout $dibsCheckout,
        DibsCheckoutContext $dibsCheckoutContext,
        Json $json
    ) {
        $this->dibsCheckout = $dibsCheckout;
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager= $storeManager;
        $this->dibsCheckoutContext = $dibsCheckoutContext;
        $this->json = $json;

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

        //check if quote was changed
        $ctrlkey = (string)$this->getRequest()->getParam('ctrlkey');

        //check if cart was updated
        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);
        $currkey = $checkout->getQuoteSignature();
        if($currkey != $ctrlkey) {
            $response = array(
                'reload'   => 1,
                'messages' =>(string)__('The cart was updated (from another location), reloading the checkout, please wait...')
            );
            $this->messageManager->addErrorMessage(__('The requested changes were not applied. The cart was updated (from another location), please review the cart.'));
            $this->getResponse()->setBody($this->json->serialize($response));

            return true;
        }

        return false;
    }

     /**
     * @return bool
     */
    protected function validateQuoteSignature()
    {
        //check if quote was changed
        $ctrlkey = (string)$this->getRequest()->getParam('ctrlkey');

        //check if cart was updated
        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);
        $currkey = $checkout->getQuoteSignature();
        if($currkey != $ctrlkey) {
            $response = array(
                'reload'   => 1,
                'messages' =>(string)__('The cart was updated (from another location), page will be reloaded.')
            );
            $this->messageManager->addErrorMessage(__('The requested changes were not applied. The cart was updated (from another location), please review the cart.'));
            $this->getResponse()->setBody($this->json->serialize($response));

            return false;
        }

        return true;
    }
}

<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;

class CartRevoke implements ActionInterface
{
    protected $resultPageFactory;

    public function __construct(Context $context, PageFactory $resultPageFactory, Session $checkoutSession, CartRepositoryInterface $quoteFactory)
    {

        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->quoteFactory = $quoteFactory;
    }

    public function execute()
    {
        $session = $this->checkoutSession;

        $quote = $this->quoteFactory->get($session->getQuoteId());
        $quote->setIsActive(true)->setReservedOrderId(null);

        //Mismatch interface but is bug from Magento 2
        $session->replaceQuote($quote);
        $this->quoteFactory->save($quote);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__("You revoked the payment and your shopping cart was restored. "));

        return $resultPage;
    }
}

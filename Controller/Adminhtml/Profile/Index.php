<?php

namespace Nexi\Checkout\Controller\Adminhtml\Profile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;

class Index implements HttpGetActionInterface
{
    /**
     * @param Context $context
     */
    public function __construct(
        private Context $context
    ) {
    }

    public function execute()
    {
        $page = $this->initialize();
        $page->getConfig()->getTitle()->prepend(__('Recurring Payment Profiles'));

        return $page;
    }

    private function initialize()
    {
        $resultPage = $this->context->getResultFactory()
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Magento_Sales::sales_order');

        return $resultPage;
    }
}

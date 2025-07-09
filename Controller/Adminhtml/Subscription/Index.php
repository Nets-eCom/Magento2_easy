<?php

namespace Nexi\Checkout\Controller\Adminhtml\Recurring;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private Context $context,
    ) {
    }

    public function execute()
    {
        $page = $this->initialize();
        $page->getConfig()->getTitle()->prepend(__('Subscriptions'));

        return $page;
    }

    private function initialize()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->context->getResultFactory()->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Magento_Sales::sales_order');

        return $resultPage;
    }
}

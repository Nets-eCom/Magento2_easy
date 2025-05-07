<?php

namespace Nexi\Checkout\Controller\Adminhtml\Profile;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Edit implements HttpGetActionInterface
{
    /**
     * @param Context $context
     */
    public function __construct(
        private Context $context,
    ) {
    }

    public function execute()
    {
        $page  = $this->initialize();
        $title = $this->context->getRequest()->getParam('id')
            ? __('Edit Subscription profile')
            : __('Add new profile');
        $page->getConfig()->getTitle()->prepend($title);

        return $page;
    }

    private function initialize()
    {
        $resultPage = $this->context->getResultFactory()->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_PAGE
        );
        $resultPage->setActiveMenu('Magento_Sales::sales_order');

        return $resultPage;
    }
}

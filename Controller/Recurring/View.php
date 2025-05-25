<?php

namespace Nexi\Checkout\Controller\Adminhtml\Recurring;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;

class View implements ActionInterface
{

    public function __construct(
        private Context $context,
    ) {
    }

    public function execute()
    {
        $page = $this->initialize();
        $page->getConfig()->getTitle()->prepend(__('View Recurring Payment'));

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

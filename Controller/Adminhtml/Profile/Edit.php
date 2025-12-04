<?php
declare(strict_types=1);

namespace Nexi\Checkout\Controller\Adminhtml\Profile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;

class Edit implements HttpGetActionInterface
{
    /**
     * @param Context $context
     */
    public function __construct(
        private Context $context
    ) {
    }

    /**
     * Executes the initialization of the page and sets the page title
     * based on the presence of the 'id' parameter in the request.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $page  = $this->initialize();
        $title = $this->context->getRequest()->getParam('id')
            ? __('Edit Subscription profile')
            : __('Add new profile');
        $page->getConfig()->getTitle()->prepend($title);

        return $page;
    }

    /**
     * Initializes and configures the result page with the active menu.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    private function initialize()
    {
        $resultPage = $this->context->getResultFactory()->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_PAGE
        );
        $resultPage->setActiveMenu('Magento_Sales::sales_order');

        return $resultPage;
    }
}

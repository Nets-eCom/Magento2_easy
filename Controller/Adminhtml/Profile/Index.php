<?php
declare(strict_types=1);

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

    /**
     * Executes the method to initialize a page and set the title to "Subscription Profiles".
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $page = $this->initialize();
        $page->getConfig()->getTitle()->prepend(__('Subscription Profiles'));

        return $page;
    }

    /**
     * Initializes the result page and sets the active menu for sales orders.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    private function initialize()
    {
        $resultPage = $this->context->getResultFactory()
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Magento_Sales::sales_order');

        return $resultPage;
    }
}

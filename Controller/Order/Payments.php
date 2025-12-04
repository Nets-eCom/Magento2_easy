<?php
declare(strict_types=1);

namespace Nexi\Checkout\Controller\Order;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Sales\Controller\OrderInterface;
use Magento\Framework\View\Result\PageFactory;

class Payments implements OrderInterface, HttpGetActionInterface
{

    /**
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        private PageFactory $resultPageFactory
    ) {
    }

    /**
     * Customer order history
     *
     * @return Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('My Subscriptions'));

        return $resultPage;
    }
}

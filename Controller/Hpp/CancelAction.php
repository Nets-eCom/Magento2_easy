<?php

namespace Nexi\Checkout\Controller\Hpp;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;

class CancelAction implements ActionInterface
{

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param UrlInterface $url
     * @param Session $checkoutSession
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        private readonly RedirectFactory   $resultRedirectFactory,
        private readonly UrlInterface      $url,
        private readonly Session           $checkoutSession,
        private readonly ManagerInterface  $messageManager
    ) {
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $this->checkoutSession->restoreQuote();
        $this->messageManager->addNoticeMessage(__('The payment has been canceled.'));

        return $this->resultRedirectFactory->create()->setUrl(
            $this->url->getUrl('checkout/cart/index', ['_secure' => true])
        );
    }
}

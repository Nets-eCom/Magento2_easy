<?php

namespace Nexi\Checkout\Controller\Hpp;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class CancelAction implements ActionInterface
{

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param UrlInterface $url
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        private readonly RedirectFactory   $resultRedirectFactory,
        private readonly UrlInterface      $url,
        private readonly LoggerInterface   $logger,
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
        $paymentId = null;
        try {
            $paymentId = $this->checkoutSession->getQuote()->getPayment()->getAdditionalInformation('payment_id');
            $this->checkoutSession->restoreQuote();
            $this->messageManager->addNoticeMessage(__('The payment has been canceled.'));
        } catch (\Exception $e) {
            $this->logger->critical(
                $e->getMessage(),
                [
                    'exception_trace' => $e->getTraceAsString(),
                    'nexi_payment_id' => $paymentId
                ]
            );

            $this->messageManager->addErrorMessage(
                __('An error occurred during the payment process. Please try again later.')
            );
        }

        return $this->resultRedirectFactory->create()->setUrl(
            $this->url->getUrl('checkout/cart/index', ['_secure' => true])
        );
    }
}

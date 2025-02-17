<?php

namespace Nexi\Checkout\Controller\Hpp;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Nexi\Checkout\Model\Config\Source\Environment;
use NexiCheckout\Factory\PaymentApiFactory;
use Psr\Log\LoggerInterface;

class CancelAction implements ActionInterface
{
    public function __construct(
        private readonly RedirectFactory   $resultRedirectFactory,
        private readonly PaymentApiFactory $paymentApiFactory,
        private readonly RequestInterface  $request,
        private readonly UrlInterface      $url,
        private readonly LoggerInterface   $logger,
        private readonly Session           $checkoutSession,
        private readonly ManagerInterface  $messageManager
    ) {
    }

    public function execute(): ResultInterface
    {
        try {
            $this->checkoutSession->restoreQuote();
            $this->messageManager->addNoticeMessage(__('The payment has been canceled.'));
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage() . ' - ' . $e->getTraceAsString());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $logId = uniqid();
            $this->logger->critical($logId . ' - ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            $this->messageManager->addErrorMessage(
                __(
                    'An error occurred during the payment process. Please try again later.' .
                    'Log ID: %1',
                    $logId
                )
            );
        }

        return $this->resultRedirectFactory->create()->setUrl(
            $this->url->getUrl('checkout/cart/index', ['_secure' => true])
        );
    }
}

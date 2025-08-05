<?php

declare(strict_types=1);

namespace Nexi\Checkout\Controller\Hpp;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderManagementInterface;

class CancelAction implements ActionInterface
{
    /**
     * CancelAction constructor.
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param UrlInterface $url
     * @param Session $checkoutSession
     * @param OrderManagementInterface $orderManagementInterface
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        private readonly RedirectFactory          $resultRedirectFactory,
        private readonly UrlInterface             $url,
        private readonly Session                  $checkoutSession,
        private readonly OrderManagementInterface $orderManagementInterface,
        private readonly ManagerInterface         $messageManager
    ) {
    }

    /**
     * Execute cancel action.
     *
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function execute(): ResultInterface
    {
        $this->checkoutSession->restoreQuote();
        $this->cancelOrderById($this->checkoutSession->getLastOrderId());
        $this->messageManager->addNoticeMessage(__('The payment has been canceled.'));

        return $this->resultRedirectFactory->create()->setUrl(
            $this->url->getUrl('checkout/cart/index', ['_secure' => true])
        );
    }

    /**
     * CancelOrderById function.
     *
     * @param $orderId
     * @return void
     * @throws LocalizedException
     */
    private function cancelOrderById($orderId): void
    {
        try {
            $this->orderManagementInterface->cancel($orderId);
        } catch (\Exception $e) {
            $this->logger->critical(sprintf(
                'Nexi exception during order cancel: %s,\n error trace: %s',
                $e->getMessage(),
                $e->getTraceAsString()
            ));

            // Mask and throw end-user friendly exception
            throw new LocalizedException(__(
                'Error while cancelling order.
                    Please contact customer support with order id: %id to release discount coupons.',
                ['id' => $orderId]
            ));
        }
    }
}

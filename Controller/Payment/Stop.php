<?php
declare(strict_types=1);

namespace Nexi\Checkout\Controller\Payment;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Api\SubscriptionLinkRepositoryInterface;
use Nexi\Checkout\Api\SubscriptionRepositoryInterface;
use Nexi\Checkout\Model\Validation\PreventAdminActions;
use Psr\Log\LoggerInterface;

class Stop implements Action\HttpGetActionInterface
{
    public const STATUS_CLOSED        = 'closed';
    public const ORDER_PENDING_STATUS = 'pending';


    /**
     * @param Context $context
     * @param Session $customerSession
     * @param SubscriptionRepositoryInterface $subscriptionRepositoryInterface
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param OrderManagementInterface $orderManagementInterface
     * @param LoggerInterface $logger
     * @param SubscriptionLinkRepositoryInterface $subscriptionLinkRepositoryInterface
     * @param PreventAdminActions $preventAdminActions
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        private Context                             $context,
        private Session                             $customerSession,
        private SubscriptionRepositoryInterface     $subscriptionRepositoryInterface,
        private OrderRepositoryInterface            $orderRepositoryInterface,
        private OrderManagementInterface            $orderManagementInterface,
        private LoggerInterface                     $logger,
        private SubscriptionLinkRepositoryInterface $subscriptionLinkRepositoryInterface,
        private PreventAdminActions                 $preventAdminActions,
        private ManagerInterface                    $messageManager
    ) {
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        $subscriptionId = $this->context->getRequest()->getParam('payment_id');
        $resultRedirect = $this->context->getResultFactory()->create(ResultFactory::TYPE_REDIRECT);

        if ($this->preventAdminActions->isAdminAsCustomer()) {
            $this->messageManager->addErrorMessage(__('Admin user is not authorized for this operation'));
            $resultRedirect->setPath('nexi/order/payments');

            return $resultRedirect;
        }

        try {
            $subscription = $this->subscriptionRepositoryInterface->get((int)$subscriptionId);
            $orderIds     = $this->subscriptionLinkRepositoryInterface->getOrderIdsBySubscriptionId(
                (int)$subscriptionId
            );

            foreach ($orderIds as $orderId) {
                $order = $this->orderRepositoryInterface->get($orderId);
                if (!$this->customerSession->getId() || $this->customerSession->getId() != $order->getCustomerId()) {
                    throw new LocalizedException(__('Customer is not authorized for this operation'));
                }
                $subscription->setStatus(self::STATUS_CLOSED);
                if ($order->getStatus() === Order::STATE_PENDING_PAYMENT
                    || $order->getStatus() === self::ORDER_PENDING_STATUS) {
                    $this->orderManagementInterface->cancel($order->getId());
                }
            }

            $this->subscriptionRepositoryInterface->save($subscription);
            $resultRedirect->setPath('nexi/order/payments');
            $this->messageManager->addSuccessMessage('Subscription stopped successfully');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage(__('Unable to stop payment'));
            $resultRedirect->setPath('nexi/order/payments');
        }

        return $resultRedirect;
    }
}

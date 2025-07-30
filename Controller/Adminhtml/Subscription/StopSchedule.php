<?php
declare(strict_types=1);

namespace Nexi\Checkout\Controller\Adminhtml\Subscription;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderManagementInterface;
use Nexi\Checkout\Api\Data\SubscriptionInterface;
use Nexi\Checkout\Api\SubscriptionLinkRepositoryInterface;
use Nexi\Checkout\Api\SubscriptionRepositoryInterface;
use Nexi\Checkout\Model\SubscriptionManagement;

class StopSchedule implements HttpGetActionInterface
{
    /**
     * StopSchedule constructor.
     *
     * @param Context $context
     * @param SubscriptionRepositoryInterface $subscriptionRepository
     * @param OrderManagementInterface $orderManagement
     * @param SubscriptionLinkRepositoryInterface $subscriptionLinkRepoInterface
     */
    public function __construct(
        private Context                             $context,
        private SubscriptionRepositoryInterface     $subscriptionRepository,
        private OrderManagementInterface            $orderManagement,
        private SubscriptionLinkRepositoryInterface $subscriptionLinkRepoInterface
    ) {
    }

    /**
     * Executes the process to cancel a recurring subscription.
     *
     * @return Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->context->getResultFactory()->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($this->_redirect->getRefererUrl());
        $id = $this->context->getRequest()->getParam('id');

        $subscription = $this->getRecurringPayment($id);
        if (!$subscription) {
            return $resultRedirect;
        }
        $this->cancelOrder($subscription);
        $this->updateRecurringStatus($subscription);

        return $resultRedirect;
    }

    /**
     * Retrieves the recurring payment subscription by its ID.
     *
     * @param $subscriptionId
     * @return false|SubscriptionInterface
     */
    private function getRecurringPayment($subscriptionId)
    {
        try {
            return $this->subscriptionRepository->get($subscriptionId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->context->getMessageManager()->addErrorMessage(
                \__(
                    'Unable to load subscription with ID: %id',
                    ['id' => $subscriptionId]
                )
            );
        }

        return false;
    }

    /**
     * Cancels the order associated with the subscription if it is unpaid.
     *
     * @param SubscriptionInterface $subscription
     * @return void
     */
    private function cancelOrder(SubscriptionInterface $subscription): void
    {
        try {
            // Only cancel unpaid orders.
            $ordersId = $this->subscriptionLinkRepoInterface->getOrderIdsBySubscriptionId($subscription->getId());
            if ($subscription->getStatus() !== SubscriptionInterface::STATUS_CLOSED) {
                foreach ($ordersId as $orderId) {
                    $this->orderManagement->cancel($orderId);
                }
            } else {
                $this->context->getMessageManager()->addWarningMessage(
                    \__(
                        'Order ID %id has a status other than %status, automatic order cancel disabled. If the order is unpaid please cancel it manually',
                        [
                            'id'     => array_shift($ordersId),
                            'status' => SubscriptionManagement::ORDER_PENDING_STATUS
                        ]
                    )
                );
            }
        } catch (LocalizedException $exception) {
            $this->context->getMessageManager()->addErrorMessage(
                \__(
                    'Error occurred while cancelling the order: %error',
                    ['error' => $exception->getMessage()]
                )
            );
        }
    }

    /**
     * Updates the recurring status of a subscription to "closed".
     *
     * @param SubscriptionInterface $subscription
     * @return void
     */
    private function updateRecurringStatus(SubscriptionInterface $subscription)
    {
        $subscription->setStatus(SubscriptionInterface::STATUS_CLOSED);

        try {
            $this->subscriptionRepository->save($subscription);
            $this->context->getMessageManager()->addSuccessMessage(
                \__(
                    'Recurring payments stopped for payment id: %id',
                    [
                        'id' => $subscription->getId()
                    ]
                )
            );
        } catch (CouldNotSaveException $exception) {
            $this->context->getMessageManager()->addErrorMessage(
                \__(
                    'Error occurred while updating recurring payment: %error',
                    ['error' => $exception->getMessage()]
                )
            );
        }
    }
}

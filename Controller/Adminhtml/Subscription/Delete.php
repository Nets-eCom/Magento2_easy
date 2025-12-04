<?php
declare(strict_types=1);

namespace Nexi\Checkout\Controller\Adminhtml\Subscription;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Nexi\Checkout\Api\SubscriptionRepositoryInterface;

class Delete implements HttpPostActionInterface
{
    /**
     * Delete constructor.
     *
     * @param Context $context
     * @param SubscriptionRepositoryInterface $subscriptionRepository
     */
    public function __construct(
        private Context                         $context,
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {
    }

    /**
     * Executes the deletion of a subscription based on the provided ID.
     *
     * @return Redirect
     */
    public function execute()
    {
        $id             = $this->context->getRequest()->getParam('id');
        $resultRedirect = $this->context->getResultFactory()->create(ResultFactory::TYPE_REDIRECT);

        try {
            $payment = $this->subscriptionRepository->get((int)$id);
            $this->subscriptionRepository->delete($payment);
            $resultRedirect->setPath('subscriptions/subscription');
            $this->context->getMessageManager()->addSuccessMessage('Subscription deleted');
        } catch (\Throwable $e) {
            $this->context->getMessageManager()->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('subscriptions/subscription/view', ['id' => $id]);
        }

        return $resultRedirect;
    }
}

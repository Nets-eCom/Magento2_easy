<?php

namespace Nexi\Checkout\Controller\Adminhtml\Recurring;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Nexi\Checkout\Api\SubscriptionRepositoryInterface;

class Delete implements HttpPostActionInterface
{
    public function __construct(
        private Context                         $context,
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {
    }

    public function execute()
    {
        $id             = $this->context->getRequest()->getParam('id');
        $resultRedirect = $this->context->getResultFactory()->create(ResultFactory::TYPE_REDIRECT);

        try {
            $payment = $this->subscriptionRepository->get($id);
            $this->subscriptionRepository->delete($payment);
            $resultRedirect->setPath('subscription/listing');
            $this->context->getMessageManager()->addSuccessMessage('Subscription deleted');
        } catch (\Throwable $e) {
            $this->context->getMessageManager()->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('subscription/listing/edit', ['id' => $id]);
        }

        return $resultRedirect;
    }
}

<?php
declare(strict_types=1);

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
            $resultRedirect->setPath('recurring_payments/recurring');
            $this->context->getMessageManager()->addSuccessMessage('Recurring payment deleted');
        } catch (\Throwable $e) {
            $this->context->getMessageManager()->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('recurring_payments/recurring/edit', ['id' => $id]);
        }

        return $resultRedirect;
    }
}

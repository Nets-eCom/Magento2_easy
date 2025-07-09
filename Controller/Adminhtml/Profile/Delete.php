<?php

namespace Nexi\Checkout\Controller\Adminhtml\Profile;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Nexi\Checkout\Api\SubscriptionProfileRepositoryInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class Delete implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param SubscriptionProfileRepositoryInterface $profileRepo
     */
    public function __construct(
        private Context                             $context,
        private SubscriptionProfileRepositoryInterface $profileRepo
    ) {
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $id             = $this->context->getRequest()->getParam('id');
        $resultRedirect = $this->context->getResultFactory()->create(ResultFactory::TYPE_REDIRECT);

        try {
            $profile = $this->profileRepo->get($id);
            $this->profileRepo->delete($profile);
            $resultRedirect->setPath('subscription/profile');
            $this->context->getMessageManager()->addSuccessMessage('Subscription profile deleted');
        } catch (\Throwable $e) {
            $this->context->getMessageManager()->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('subscription/profile/edit', ['id' => $id]);
        }

        return $resultRedirect;
    }
}

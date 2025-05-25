<?php

namespace Nexi\Checkout\Controller\Adminhtml\Recurring;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Nexi\Checkout\Api\Data\SubscriptionLinkInterfaceFactory;
use Nexi\Checkout\Api\SubscriptionRepositoryInterface;

class Save implements HttpPostActionInterface
{

    public function __construct(
        private Context                          $context,
        private SubscriptionRepositoryInterface  $paymentRepo,
        private SubscriptionLinkInterfaceFactory $factory
    ) {
    }

    public function execute()
    {
        $id = $this->context->getRequest()->getParam('entity_id');

        if ($id) {
            $payment = $this->paymentRepo->get($id);
        } else {
            $payment = $this->factory->create();
        }

        $data = $this->getRequest()->getParams();
        $payment->setData($data);
        $resultRedirect = $this->context->getResultFactory()->create(ResultFactory::TYPE_REDIRECT);
        try {
            $this->paymentRepo->save($payment);
            $resultRedirect->setPath('recurring_payments/recurring');
        } catch (CouldNotSaveException $e) {
            $this->context->getMessageManager()->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('recurring_payments/recurring/edit', ['id' => $id]);
        }

        return $resultRedirect;
    }
}

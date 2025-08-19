<?php
declare(strict_types=1);

namespace Nexi\Checkout\Controller\Adminhtml\Subscription;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Nexi\Checkout\Api\Data\SubscriptionLinkInterfaceFactory;
use Nexi\Checkout\Api\SubscriptionRepositoryInterface;

class Save implements HttpPostActionInterface
{
    /**
     * Save constructor.
     *
     * @param Context $context
     * @param SubscriptionRepositoryInterface $paymentRepositoryInterface
     * @param SubscriptionLinkInterfaceFactory $factory
     */
    public function __construct(
        private Context                          $context,
        private SubscriptionRepositoryInterface  $paymentRepositoryInterface,
        private SubscriptionLinkInterfaceFactory $factory
    ) {
    }

    /**
     * Executes the process of saving a payment entity.
     *
     * @return Redirect
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $id = $this->context->getRequest()->getParam('entity_id');

        if ($id) {
            $payment = $this->paymentRepositoryInterface->get($id);
        } else {
            $payment = $this->factory->create();
        }

        $data = $this->getRequest()->getParams();
        $payment->setData($data);
        $resultRedirect = $this->context->getResultFactory()->create(ResultFactory::TYPE_REDIRECT);
        try {
            $this->paymentRepositoryInterface->save($payment);
            $resultRedirect->setPath('recurring_payments/recurring');
        } catch (CouldNotSaveException $e) {
            $this->context->getMessageManager()->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('recurring_payments/recurring/edit', ['id' => $id]);
        }

        return $resultRedirect;
    }
}

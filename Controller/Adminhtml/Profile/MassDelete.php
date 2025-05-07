<?php

namespace Nexi\Checkout\Controller\Adminhtml\Profile;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
//use Nexi\Checkout\Model\ResourceModel\Subscription\Profile;
//use Nexi\Checkout\Model\ResourceModel\Subscription\Profile\CollectionFactory;

class MassDelete implements HttpPostActionInterface
{
    public function __construct(
        private Context           $context,
        private Filter            $filter,
//        private CollectionFactory $factory,
//        private Profile           $profileResource
    ) {
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->context->getResultFactory()->create(ResultFactory::TYPE_REDIRECT);

//        $collection     = $this->filter->getCollection($this->factory->create());
//        $collectionSize = $collection->getSize();
//
//        foreach ($collection as $item) {
//            $this->profileResource->delete($item);
//        }
//
//        $this->context->getMessageManager()->addSuccessMessage(
//            __('A total of %1 record(s) have been deleted.', $collectionSize)
//        );

        return $resultRedirect->setPath('*/*/');
    }
}

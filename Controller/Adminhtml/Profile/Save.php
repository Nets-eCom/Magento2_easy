<?php

namespace Nexi\Checkout\Controller\Adminhtml\Profile;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Nexi\Checkout\Api\Data\RecurringProfileInterfaceFactory;
use Nexi\Checkout\Api\RecurringProfileRepositoryInterface;

class Save implements HttpPostActionInterface
{
    const DELETE_QUOTE_AFTER = 'checkout/cart/delete_quote_after';


    public function __construct(
        private Context                             $context,
        private RecurringProfileRepositoryInterface $profileRepo,
        private RecurringProfileInterfaceFactory    $factory,
        private SerializerInterface                 $serializer,
        private ScopeConfigInterface                $scopeConfig
    ) {
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $id = $this->context->getRequest()->getParam('profile_id');
        if ($id) {
            $profile = $this->profileRepo->get($id);
        } else {
            $profile = $this->factory->create();
        }

        $data           = $this->getRequestData();
        $resultRedirect = $this->context->getResultFactory()->create(ResultFactory::TYPE_REDIRECT);

        if ($this->validateProfile($data) === false) {
            $this->context->getMessageManager()->addErrorMessage(
                "Schedule can't be saved due to the profile's payment period exceeding your store's quote lifetime.
                Please make sure the quote lifetime is longer than your profile's payment schedule in days.
                See Stores->Configuration->Sales->Checkout->Shopping Cart->Quote Lifetime (days)"
            );
            $resultRedirect->setPath('recurring_payments/profile/edit', ['id' => $id]);
            return $resultRedirect;
        }

        $profile->setData($data);
        try {
            $this->profileRepo->save($profile);
            $resultRedirect->setPath('recurring_payments/profile');
        } catch (CouldNotSaveException $e) {
            $this->context->getMessageManager()->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('recurring_payments/profile/edit', ['id' => $id]);
        }

        return $resultRedirect;
    }

    private function getRequestData()
    {
        $data = $this->context->getRequest()->getParams();

        if (isset($data['interval_period']) && isset($data['interval_unit'])) {
            $schedule = [
                'interval' => $data['interval_period'],
                'unit'     => $data['interval_unit'],
            ];

            $data['schedule'] = $this->serializer->serialize($schedule);
        }

        if (!$data['profile_id']) {
            unset($data['profile_id']);
        }

        return $data;
    }

    private function validateProfile($data)
    {
        $quoteLimit = $this->scopeConfig->getValue(
            self::DELETE_QUOTE_AFTER,
            ScopeInterface::SCOPE_STORE
        );
        switch ($data['interval_unit']) {
            case 'D':
                $days = 1;
                break;
            case 'W':
                $days = 7;
                break;
            case 'M':
                $days = 30.436875;
                break;
            case 'Y':
                $days = 365.2425;
                break;
        }
        if (isset($days) &&$data['interval_period'] * $days > $quoteLimit) {
            return false;}

        return true;
    }
}

<?php

namespace Nexi\Checkout\Model\Subscription;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Nexi\Checkout\Api\Data;
use Nexi\Checkout\Api\Data\SubscriptionProfileSearchResultInterface;
use Nexi\Checkout\Api\SubscriptionProfileRepositoryInterface;
use Nexi\Checkout\Model\Subscription;

class ProfileRepository implements SubscriptionProfileRepositoryInterface
{
    /**
     * @var \Nexi\Checkout\Model\ResourceModel\Subscription\Profile
     */
    private $profileResource;
    /**
     * @var Data\SubscriptionProfileInterfaceFactory
     */
    private $profileFactory;
    /**
     * @var \Nexi\Checkout\Model\ResourceModel\Subscription\Profile
     */
    private $profileResultFactory;
    /**
     * @var \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface
     */
    private $collectionProcessor;

    public function __construct(
        \Nexi\Checkout\Model\ResourceModel\Subscription\Profile              $profileResource,
        \Nexi\Checkout\Api\Data\SubscriptionProfileInterfaceFactory            $profileFactory,
        \Nexi\Checkout\Api\Data\SubscriptionProfileSearchResultInterfaceFactory $profileResultFactory,
        \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor
    ) {
        $this->profileResource = $profileResource;
        $this->profileFactory = $profileFactory;
        $this->profileResultFactory = $profileResultFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function get($profileId)
    {
        /** @var Subscription $subscription */
        $subscription = $this->profileFactory->create();
        $this->profileResource->load($subscription, $profileId);

        if (!$subscription->getId()) {
            throw new NoSuchEntityException(\__(
                'No subscription profile found with id %id',
                [
                    'id' => $profileId
                ]
            ));
        }

        return $subscription;
    }

    /**
     * @inheritDoc
     */
    public function save(Data\SubscriptionProfileInterface $profile)
    {
        try {
            $this->profileResource->save($profile);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(\__(
                'Could not save Recurring Profile: %error',
                ['error' => $e->getMessage()]
            ));
        }

        return $profile;
    }

    /**
     * @inheritDoc
     */
    public function delete(Data\SubscriptionProfileInterface $profile)
    {
        try {
            $this->profileResource->delete($profile);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__(
                'Unable to delete recurring profile: %error',
                ['error' => $e->getMessage()]
            ));
        }
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ) : SubscriptionProfileSearchResultInterface {
        /** @var Data\RecurringProfileSearchResultInterface $searchResult */
        $searchResult = $this->profileResultFactory->create();
        $this->collectionProcessor->process($searchCriteria, $searchResult);
        $searchResult->setSearchCriteria($searchCriteria);

        return $searchResult;
    }
}

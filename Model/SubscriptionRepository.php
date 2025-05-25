<?php

namespace Nexi\Checkout\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Nexi\Checkout\Api\Data\SubscriptionInterface;
use Nexi\Checkout\Api\Data\SubscriptionSearchResultInterface;
use Nexi\Checkout\Api\SubscriptionRepositoryInterface;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    private ResourceModel\Subscription $subscriptionResource;
    private SubscriptionFactory $subscriptionFactory;
    private \Nexi\Checkout\Api\Data\SubscriptionSearchResultInterfaceFactory $searchResultFactory;
    private \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor;

    public function __construct(
        \Nexi\Checkout\Model\ResourceModel\Subscription $subscriptionResource,
        SubscriptionFactory $subscriptionFactory,
        \Nexi\Checkout\Api\Data\SubscriptionSearchResultInterfaceFactory $searchResultFactory,
        \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor
    ) {
        $this->subscriptionResource = $subscriptionResource;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->searchResultFactory = $searchResultFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    public function get(int $entityId): SubscriptionInterface
    {
        /** @var Subscription $subscription */
        $subscription = $this->subscriptionFactory->create();
        $this->subscriptionResource->load($subscription, $entityId);

        if (!$subscription->getId()) {
            throw new NoSuchEntityException(\__(
                'No recurring payment found with id %id',
                [
                    'id' => $entityId
                ]
            ));
        }

        return $subscription;
    }

    public function save(SubscriptionInterface $subscription): SubscriptionInterface
    {
        try {
            $this->subscriptionResource->save($subscription);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(\__(
                'Could not save Recurring Payment: %error',
                ['error' => $e->getMessage()]
            ));
        }

        return $subscription;
    }

    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ): SubscriptionSearchResultInterface {
        /** @var SubscriptionSearchResultInterface $searchResult */
        $searchResult = $this->searchResultFactory->create();
        $this->collectionProcessor->process($searchCriteria, $searchResult);
        $searchResult->setSearchCriteria($searchCriteria);

        return $searchResult;
    }

    public function delete(SubscriptionInterface $subscription)
    {
        try {
            $this->subscriptionResource->delete($subscription);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__(
                'Unable to delete recurring payment: %error',
                ['error' => $e->getMessage()]
            ));
        }
    }
}

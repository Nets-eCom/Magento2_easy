<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Nexi\Checkout\Api\Data\SubscriptionInterface;
use Nexi\Checkout\Api\Data\SubscriptionSearchResultInterface;
use Nexi\Checkout\Api\Data\SubscriptionSearchResultInterfaceFactory;
use Nexi\Checkout\Api\SubscriptionRepositoryInterface;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    /**
     * SubscriptionRepository constructor.
     *
     * @param ResourceModel\Subscription $subscriptionResource
     * @param SubscriptionFactory $subscriptionFactory
     * @param SubscriptionSearchResultInterfaceFactory $searchResultFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        private \Nexi\Checkout\Model\ResourceModel\Subscription $subscriptionResource,
        private SubscriptionFactory $subscriptionFactory,
        private SubscriptionSearchResultInterfaceFactory $searchResultFactory,
        private CollectionProcessorInterface $collectionProcessor
    ) {
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

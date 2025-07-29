<?php

namespace Nexi\Checkout\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Nexi\Checkout\Api\Data\SubscriptionInterface;
use Nexi\Checkout\Api\Data\SubscriptionSearchResultInterface;

interface SubscriptionRepositoryInterface
{
    /**
     * Get subscription.
     *
     * @param int $entityId
     * @return SubscriptionInterface
     *
     * @throws NoSuchEntityException
     */
    public function get(int $entityId): SubscriptionInterface;

    /**
     * Save subscription.
     *
     * @param SubscriptionInterface $subscription
     * @return SubscriptionInterface
     *
     * @throws CouldNotSaveException
     */
    public function save(SubscriptionInterface $subscription): SubscriptionInterface;

    /**
     * Get list of subscriptions.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Nexi\Checkout\Api\Data\SubscriptionSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SubscriptionSearchResultInterface;

    /**
     * Delete subscription.
     *
     * @param SubscriptionInterface $subscription
     * @return void
     *
     * @throws CouldNotDeleteException
     */
    public function delete(SubscriptionInterface $subscription);
}

<?php

namespace Nexi\Checkout\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * @api
 */
interface SubscriptionManagementInterface
{
    /**
     * Cancel active subscription.
     *
     * @param string $subscriptionId
     * @return string
     */
    public function cancelSubscription(string $subscriptionId);

    /**
     * Shows customer subscriptions.
     *
     * @param  \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Nexi\Checkout\Api\Data\SubscriptionSearchResultInterface
     */
    public function showSubscriptions(SearchCriteriaInterface $searchCriteria);

    /**
     * Change assigned card for subscription.
     *
     * @param string $subscriptionId
     * @param string $cardId
     *
     * @return bool
     */
    public function changeSubscription(string $subscriptionId, string $cardId): bool;
}

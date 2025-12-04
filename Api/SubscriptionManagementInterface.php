<?php
declare(strict_types=1);

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
     * Change subscription.
     *
     * @param string $subscriptionId
     *
     * @return bool
     */
    public function changeSubscription(string $subscriptionId): bool;
}

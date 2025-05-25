<?php

namespace Nexi\Checkout\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface SubscriptionLinkRepositoryInterface
{
    /**
     * Get link.
     *
     * @param int $linkId
     * @return Data\SubscriptionLinkInterface
     * @throws NoSuchEntityException
     */
    public function get($linkId);

    /**
     * Save link.
     *
     * @param Data\SubscriptionLinkInterface $subscriptionLink
     * @return Data\SubscriptionLinkInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\SubscriptionLinkInterface $subscriptionLink);

    /**
     * Delete link.
     *
     * @param Data\SubscriptionLinkInterface $subscriptionLink
     * @throws CouldNotDeleteException
     */
    public function delete(Data\SubscriptionLinkInterface $subscriptionLink);

    /**
     * Get list of links.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return Data\SubscriptionLinkSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): Data\SubscriptionLinkSearchResultInterface;

    /**
     * Get subscription from order ID.
     *
     * @param int $orderId
     * @return mixed
     */
    public function getSubscriptionFromOrderId($orderId);

    /**
     * Link order to subscription.
     *
     * @param int $orderId
     * @param int $subscriptionId
     * @return mixed
     */
    public function linkOrderToSubscription($orderId, $subscriptionId);

    /**
     * Get order IDs from subscription ID.
     *
     * @param int $subscriptionId
     * @return mixed
     */
    public function getOrderIdsBySubscriptionId($subscriptionId);
}

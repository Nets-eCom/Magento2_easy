<?php
declare(strict_types=1);

namespace Nexi\Checkout\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface SubscriptionProfileRepositoryInterface
{
    /**
     * Get profile.
     *
     * @param int $profileId
     * @return Data\SubscriptionProfileInterface
     * @throws NoSuchEntityException
     */
    public function get($profileId);

    /**
     * Save profile.
     *
     * @param Data\SubscriptionProfileInterface $profile
     * @return Data\SubscriptionProfileInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\SubscriptionProfileInterface $profile);

    /**
     * Delete profile.
     *
     * @param Data\SubscriptionProfileInterface $profile
     * @throws CouldNotDeleteException
     */
    public function delete(Data\SubscriptionProfileInterface $profile);

    /**
     * Get list of profiles.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return Data\SubscriptionProfileSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): Data\SubscriptionProfileSearchResultInterface;
}

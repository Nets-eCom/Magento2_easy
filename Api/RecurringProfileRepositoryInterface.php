<?php

namespace Nexi\Checkout\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Nexi\Checkout\Api\Data;

interface RecurringProfileRepositoryInterface
{
    /**
     * Get profile.
     *
     * @param int $profileId
     * @return Data\RecurringProfileInterface
     * @throws NoSuchEntityException
     */
    public function get($profileId);

    /**
     * Save profile.
     *
     * @param Data\RecurringProfileInterface $profile
     * @return Data\RecurringProfileInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\RecurringProfileInterface $profile);

    /**
     * Delete profile.
     *
     * @param Data\RecurringProfileInterface $profile
     * @throws CouldNotDeleteException
     */
    public function delete(Data\RecurringProfileInterface $profile);

    /**
     * Get list of profiles.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return Data\RecurringProfileSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): Data\RecurringProfileSearchResultInterface;
}

<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\ResourceModel\Subscriptions\Profile;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Nexi\Checkout\Api\Data\SubscriptionProfileSearchResultInterface;
use Nexi\Checkout\Model\Subscriptions\Profile;
use Nexi\Checkout\Model\ResourceModel\Subscriptions\Profile as ProfileResource;

class Collection extends AbstractCollection implements SubscriptionProfileSearchResultInterface
{
    /** @var \Magento\Framework\Api\SearchCriteriaInterface */
    private $searchCriteria;

    protected function _construct()
    {
        $this->_init(
            Profile::class,
            ProfileResource::class
        );
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\DataObject[] $items
     * @return \Nexi\Checkout\Model\ResourceModel\Subscriptions\Profile\Collection
     * @throws \Exception
     */
    public function setItems(array $items = null)
    {
        if (!$items) {
            return $this;
        }
        foreach ($items as $item) {
            $this->addItem($item);
        }
        return $this;
    }

    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this|Collection
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $this->searchCriteria = $searchCriteria;

        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return \Nexi\Checkout\Model\ResourceModel\Subscriptions\Profile\Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTotalCount($totalCount)
    {
        // total count is the collections size, do not modify it.
        return $this;
    }
}

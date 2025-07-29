<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\ResourceModel\Subscription;

use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Nexi\Checkout\Api\Data\SubscriptionSearchResultInterface;

class Collection extends AbstractCollection implements SubscriptionSearchResultInterface
{
    /** @var \Magento\Framework\Api\SearchCriteriaInterface */
    private $searchCriteria;

    protected function _construct()
    {
        $this->_init(
            \Nexi\Checkout\Model\Subscription::class,
            \Nexi\Checkout\Model\ResourceModel\Subscription::class
        );
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     * @return $this
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

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTotalCount($totalCount)
    {
        // total count is the collections size, do not modify it.
        return $this;
    }

    public function getBillingCollectionByOrderIds($orderIds)
    {
        $this->join(
            ['links' => SubscriptionLink::LINK_TABLE_NAME],
            $this->getConnection()->quoteInto(
                'links.subscription_id = main_table.entity_id AND links.order_id IN (?)',
                $orderIds
            ),
            ['order_id']
        );

        return $this;
    }
}

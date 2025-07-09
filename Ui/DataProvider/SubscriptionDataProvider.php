<?php

namespace Nexi\Checkout\Ui\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Nexi\Checkout\Model\ResourceModel\Subscription\Collection;
use Nexi\Checkout\Model\ResourceModel\Subscription\CollectionFactory;

class SubscriptionDataProvider extends AbstractDataProvider
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );

        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $collection = $this->getCollection();

        return $collection->toArray();
    }

    /**
     * @param Filter $filter
     * @return mixed|void
     */
    public function addFilter(Filter $filter)
    {
        if ($filter->getField() == 'entity_id') {
            $filter->setField('main_table.entity_id');
        }

        parent::addFilter($filter);
    }

    /**
     * @return AbstractCollection|Collection
     */
    public function getCollection()
    {
        if (!$this->collection) {
            $this->collection = $this->collectionFactory->create();
            $this->collection->getSelect()
                ->join(
                    ['cu' => 'customer_entity'],
                    'main_table.customer_id = cu.entity_id',
                    ['cu.email']
                )->join(
                    ['sublink' => 'nexi_subscription_link'],
                    'main_table.entity_id = sublink.subscription_id',
                    ['last_order_id' => 'MAX(sublink.order_id)']
                )->join(
                    ['profile' => 'recurring_payment_profiles'],
                    'main_table.recurring_profile_id = profile.profile_id',
                    ['profile_name' => 'profile.name']
                )->group('main_table.entity_id');
        }

        return $this->collection;
    }
}

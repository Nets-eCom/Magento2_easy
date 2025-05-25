<?php

namespace Nexi\Checkout\Ui\DataProvider;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Nexi\Checkout\Model\ResourceModel\Subscription\Profile\Collection;
use Nexi\Checkout\Model\ResourceModel\Subscription\Profile\CollectionFactory;

class RecurringProfileForm extends AbstractDataProvider
{
    /** @var array */
    private $loadedData;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var array|bool|float|int|string|null
     */
    private $schedule;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param SerializerInterface $serializer
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        SerializerInterface $serializer,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collectionFactory = $collectionFactory;
        $this->serializer = $serializer;
    }

    /**
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        foreach ($this->getCollection() as $recurringProfile) {
            $recurringProfile->setData('interval_period', $this->parseSchedule('interval', $recurringProfile));
            $recurringProfile->setData('interval_unit', $this->parseSchedule('unit', $recurringProfile));
            $this->loadedData[$recurringProfile->getId()] = $recurringProfile->getData();
        }

        return $this->loadedData;
    }

    /**
     * @return AbstractCollection|Collection
     */
    public function getCollection()
    {
        if (!$this->collection) {
            $this->collection = $this->collectionFactory->create();
        }

        return $this->collection;
    }

    /**
     * @param string $value
     * @param $recurringProfile
     * @return mixed|string|null
     */
    private function parseSchedule(string $value, $recurringProfile)
    {
        if (!$this->schedule) {
            try {
                $this->schedule = $this->serializer->unserialize($recurringProfile->getSchedule());
            } catch (\InvalidArgumentException $e) {
                $this->schedule = [];
            }
        }

        return $this->schedule[$value] ?? null;
    }
}

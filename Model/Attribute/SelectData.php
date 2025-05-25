<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Attribute;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Nexi\Checkout\Model\ResourceModel\Subscription\Profile\CollectionFactory;

class SelectData extends AbstractSource
{
    public const NO_RECURRING_PAYMENT_VALUE = null;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get all options
     * @return array
     */
    public function getAllOptions()
    {
        $profilesCollection = $this->collectionFactory->create();
        $collectionData = $profilesCollection->getData();

        if (!$this->_options && $collectionData) {
            foreach ($collectionData as $data) {
                if (isset($data['schedule']) && isset($data['profile_id'])) {
                    $this->_options[] = ['label' => $data['name'], 'value' => $data['profile_id']];
                }
            }
            $this->_options[] = ['label' => __('No recurring payment'), 'value' => self::NO_RECURRING_PAYMENT_VALUE];
        }

        return $this->_options;
    }
}

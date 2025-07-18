<?php
namespace Nexi\Checkout\Model;

use Nexi\Checkout\Api\Data\SubscriptionInterface;

class Subscription extends \Magento\Framework\Model\AbstractModel implements SubscriptionInterface
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Nexi\Checkout\Model\ResourceModel\Subscription::class);
    }

    public function getId()
    {
        return $this->getData(SubscriptionInterface::FIELD_ENTITY_ID);
    }

    public function getCustomerId()
    {
        return $this->getData(SubscriptionInterface::FIELD_CUSTOMER_ID);
    }

    public function getStatus(): string
    {
        return $this->getData(SubscriptionInterface::FIELD_STATUS);
    }

    public function getNextOrderDate(): string
    {
        return $this->getData(SubscriptionInterface::FIELD_NEXT_ORDER_DATE);
    }

    public function getRecurringProfileId(): int
    {
        return $this->getData(SubscriptionInterface::FIELD_RECURRING_PROFILE_ID);
    }

    public function getUpdatedAt(): string
    {
        return $this->getData(SubscriptionInterface::FIELD_UPDATED_AT);
    }

    public function getRepeatCountLeft(): int
    {
        return $this->getData(SubscriptionInterface::FIELD_REPEAT_COUNT_LEFT);
    }

    public function getRetryCount(): int
    {
        return $this->getData(SubscriptionInterface::FIELD_RETRY_COUNT);
    }

    public function setId($entityId): self
    {
        return $this->setData(SubscriptionInterface::FIELD_ENTITY_ID, $entityId);
    }

    public function setCustomerId($customerId): self
    {
        return $this->setData(SubscriptionInterface::FIELD_CUSTOMER_ID, $customerId);
    }

    public function setStatus($status): SubscriptionInterface
    {
        return $this->setData(SubscriptionInterface::FIELD_STATUS, $status);
    }

    public function setNextOrderDate(string $date): SubscriptionInterface
    {
        return $this->setData(SubscriptionInterface::FIELD_NEXT_ORDER_DATE, $date);
    }

    public function setRecurringProfileId(int $profileId): SubscriptionInterface
    {
        return $this->setData(SubscriptionInterface::FIELD_RECURRING_PROFILE_ID, $profileId);
    }

    public function setUpdatedAt(string $updatedAt): SubscriptionInterface
    {
        return $this->setData(SubscriptionInterface::FIELD_UPDATED_AT, $updatedAt);
    }

    public function setRepeatCountLeft(int $count): SubscriptionInterface
    {
        return $this->setData(SubscriptionInterface::FIELD_REPEAT_COUNT_LEFT, $count);
    }

    public function setRetryCount(int $count): SubscriptionInterface
    {
        return $this->setData(SubscriptionInterface::FIELD_RETRY_COUNT, $count);
    }
}

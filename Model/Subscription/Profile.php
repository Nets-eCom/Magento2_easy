<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Subscription;

use Magento\Framework\Model\AbstractModel;
use Nexi\Checkout\Api\Data\SubscriptionProfileInterface;

class Profile extends AbstractModel implements SubscriptionProfileInterface
{
    protected function _construct()
    {
        $this->_init(\Nexi\Checkout\Model\ResourceModel\Subscription\Profile::class);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->getData(SubscriptionProfileInterface::FIELD_PROFILE_ID);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getData(SubscriptionProfileInterface::FIELD_NAME);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getData(SubscriptionProfileInterface::FIELD_DESCRIPTION);
    }

    /**
     * @return string
     */
    public function getSchedule()
    {
        return $this->getData(SubscriptionProfileInterface::FIELD_SCHEDULE);
    }

    public function setId($profileId): self
    {
        return $this->setData(SubscriptionProfileInterface::FIELD_PROFILE_ID, $profileId);
    }

    public function setName($name): self
    {
        return $this->setData(SubscriptionProfileInterface::FIELD_NAME, $name);
    }

    public function setDescription($description): self
    {
        return $this->setData(SubscriptionProfileInterface::FIELD_DESCRIPTION, $description);
    }

    public function setSchedule($schedule): self
    {
        return $this->setData(SubscriptionProfileInterface::FIELD_SCHEDULE, $schedule);
    }
}

<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Subscription;

use Magento\Framework\Model\AbstractModel;
use Nexi\Checkout\Api\Data\SubscriptionLinkInterface;

class SubscriptionLink extends AbstractModel implements SubscriptionLinkInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Nexi\Checkout\Model\ResourceModel\Subscription\SubscriptionLink::class);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::FIELD_LINK_ID);
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->getData(self::FIELD_ORDER_ID);
    }

    /**
     * @return string
     */
    public function getSubscriptionId()
    {
        return $this->getData(self::FIELD_SUBSCRIPTION_ID);
    }

    /**
     * @param $linkId
     * @return $this
     */
    public function setId($linkId): self
    {
        return $this->setData(self::FIELD_LINK_ID, $linkId);
    }

    /**
     * @param $orderId
     * @return $this
     */
    public function setOrderId($orderId): self
    {
        return $this->setData(self::FIELD_ORDER_ID, $orderId);
    }

    /**
     * @param $subscriptionId
     * @return $this
     */
    public function setSubscriptionId($subscriptionId): self
    {
        return $this->setData(self::FIELD_SUBSCRIPTION_ID, $subscriptionId);
    }
}

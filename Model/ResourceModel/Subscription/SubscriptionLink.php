<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\ResourceModel\Subscription;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;
use Nexi\Checkout\Api\Data\SubscriptionLinkInterface;

class SubscriptionLink extends AbstractDb
{
    public const LINK_TABLE_NAME = 'nexi_subscription_link';
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::LINK_TABLE_NAME, SubscriptionLinkInterface::FIELD_LINK_ID);
    }
}

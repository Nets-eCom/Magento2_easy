<?php

namespace Nexi\Checkout\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

class RecurringToOrderGrid
{
    /**
     * @param Collection $subject
     * @return null
     * @throws LocalizedException
     */
    public function beforeLoad(Collection $subject)
    {
        if (!$subject->isLoaded()) {
            $primaryKey = $subject->getResource()->getIdFieldName();
            $tableName = $subject->getResource()->getTable('nexi_subscription_link');

            $subject->getSelect()->joinLeft(
                $tableName,
                'main_table.' . $primaryKey . ' = ' . $tableName . '.order_id',
                'subscription_id'
            );

            $subject->getSelect()->joinLeft(
                $subject->getResource()->getTable('nexi_subscriptions'),
                $tableName . '.subscription_id = nexi_subscriptions.entity_id',
                [
                    'recurring_status' => 'nexi_subscriptions.status',
                    'customer_id' => 'nexi_subscriptions.customer_id',
                    'subscription_id' => 'nexi_subscriptions.entity_id'
                ]
            );

            $subject->getSelect()->joinLeft(
                $subject->getResource()->getTable('recurring_payment_profiles'),
                'nexi_subscriptions.recurring_profile_id = recurring_payment_profiles.profile_id',
                ['recurring_profile' => 'recurring_payment_profiles.name']
            );
        }

        return null;
    }
}

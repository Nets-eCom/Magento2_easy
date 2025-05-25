<?php

namespace Nexi\Checkout\Model\OptionSource;

use \Nexi\Checkout\Api\Data\SubscriptionInterface;

class SubscriptionStatus implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => SubscriptionInterface::STATUS_PENDING_PAYMENT,
                'label' => __('Pending Payment')
            ],
            [
                'value' => SubscriptionInterface::STATUS_ACTIVE,
                'label' => __('Paid')
            ],
            [
                'value' => SubscriptionInterface::STATUS_CLOSED,
                'label' => __('Closed')
            ],
            [
                'value' => SubscriptionInterface::STATUS_FAILED,
                'label' => __('Failed')
            ],
            [
                'value' => SubscriptionInterface::STATUS_RESCHEDULED,
                'label' => __('Rescheduled')
            ],
        ];
    }
}

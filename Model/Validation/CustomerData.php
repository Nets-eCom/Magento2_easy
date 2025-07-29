<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Validation;

use Magento\Framework\Exception\LocalizedException;
use Nexi\Checkout\Api\Data\SubscriptionInterface;

class CustomerData
{
    /**
     * @param SubscriptionInterface $subscription
     * @param int $customerId
     * @return void
     * @throws LocalizedException
     */
    public function validateSubscriptionsCustomer(SubscriptionInterface $subscription, int $customerId): void
    {
        if ((int)$subscription->getCustomerId() !== $customerId) {
            throw new LocalizedException(__("The subscription doesn't belong to the customer"));
        }
    }
}

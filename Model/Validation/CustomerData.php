<?php

namespace Nexi\Checkout\Model\Validation;

use Magento\Framework\Exception\LocalizedException;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Nexi\Checkout\Api\Data\SubscriptionInterface;

class CustomerData
{
    /**
     * @param PaymentTokenInterface $paymentToken
     * @param int $customerId
     * @return void
     * @throws LocalizedException
     */
    public function validateTokensCustomer(PaymentTokenInterface $paymentToken, int $customerId): void
    {
        if ((int) $paymentToken->getCustomerId() !== $customerId) {
            throw new LocalizedException(__("The payment token doesn't belong to the customer"));
        }
    }

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

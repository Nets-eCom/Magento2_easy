<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Subscription;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Nexi\Checkout\Api\Data\SubscriptionInterface;
use Nexi\Checkout\Api\Data\SubscriptionInterfaceFactory;

class QuoteToOrder
{
    /**
     * QuoteToOrder constructor.
     *
     * @param SubscriptionInterfaceFactory $subscriptionFactory
     * @param NextDateCalculator $dateCalculator
     * @param OrderExtensionFactory $extensionFactory
     */
    public function __construct(
        private SubscriptionInterfaceFactory $subscriptionFactory,
        private NextDateCalculator           $dateCalculator,
        private OrderExtensionFactory        $extensionFactory
    ) {
    }

    /**
     * Adds a recurring payment subscription to the order based on the quote data.
     *
     * @param $order
     * @param $quote
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addRecurringPaymentToOrder($order, $quote)
    {
        $oldPayment = $quote->getData('old_order_recurring_payment');
        if (!$oldPayment) {
            return;
        }
        $extensionAttributes = $order->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->extensionFactory->create();
        }
        $extensionAttributes->setRecurringPayment($this->createNewRecurringPayment($oldPayment));
        $order->setExtensionAttributes($extensionAttributes);
    }

    /**
     * Creates a new recurring payment subscription based on the provided old payment.
     *
     * @param $oldSubscription
     * @return SubscriptionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createNewRecurringPayment($oldSubscription): SubscriptionInterface
    {
        /** @var \Nexi\Checkout\Api\Data\SubscriptionInterface $subscription */
        $subscription = $this->subscriptionFactory->create();
        $subscription->setStatus(\Nexi\Checkout\Api\Data\SubscriptionInterface::STATUS_PENDING_PAYMENT);
        $subscription->setCustomerId($oldSubscription->getCustomerId());
        $subscription->setNextOrderDate($this->dateCalculator->getNextDate($oldSubscription->getRecurringProfileId()));
        $subscription->setRecurringProfileId($oldSubscription->getRecurringProfileId());
        $subscription->setRepeatCountLeft($oldSubscription->getRepeatCountLeft() - 1);
        $subscription->setRetryCount(5);

        return $subscription;
    }
}

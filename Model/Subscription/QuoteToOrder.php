<?php

namespace Nexi\Checkout\Model\Subscription;

use Nexi\Checkout\Api\Data\SubscriptionInterface;
use Nexi\Checkout\Observer\RecurringPaymentFromQuoteToOrder;

class QuoteToOrder
{
    /**
     * @var \Nexi\Checkout\Api\Data\SubscriptionInterfaceFactory
     */
    private $subscriptionFactory;

    /**
     * @var \Magento\Sales\Api\Data\OrderExtensionFactory
     */
    private $orderExtensionFactory;
    /**
     * @var NextDateCalculator
     */
    private $dateCalculator;

    public function __construct(
        \Nexi\Checkout\Api\Data\SubscriptionInterfaceFactory $subscriptionFactory,
        \Nexi\Checkout\Model\Subscription\NextDateCalculator $dateCalculator,
        \Magento\Sales\Api\Data\OrderExtensionFactory      $extensionFactory
    ) {
        $this->subscriptionFactory = $subscriptionFactory;
        $this->orderExtensionFactory = $extensionFactory;
        $this->dateCalculator = $dateCalculator;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Quote\Model\Quote $quote
     */
    public function addRecurringPaymentToOrder($order, $quote)
    {
        $oldPayment = $quote->getData('old_order_recurring_payment');
        if (!$oldPayment) {
            return;
        }

        $extensionAttributes = $order->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->orderExtensionFactory->create();
        }
        $extensionAttributes->setRecurringPayment($this->createNewRecurringPayment($oldPayment));
        $order->setExtensionAttributes($extensionAttributes);
    }

    /**
     * @param \Nexi\Checkout\Api\Data\SubscriptionInterface $oldPayment
     * @return SubscriptionInterface
     */
    private function createNewRecurringPayment(
        $oldPayment
    ): SubscriptionInterface {
        /** @var \Nexi\Checkout\Api\Data\SubscriptionInterface $subscription */
        $subscription = $this->subscriptionFactory->create();
        $subscription->setStatus(\Nexi\Checkout\Api\Data\SubscriptionInterface::STATUS_PENDING_PAYMENT);
        $subscription->setCustomerId($oldPayment->getCustomerId());
        $subscription->setNextOrderDate($this->dateCalculator->getNextDate($oldPayment->getRecurringProfileId()));
        $subscription->setRecurringProfileId($oldPayment->getRecurringProfileId());
        $subscription->setRepeatCountLeft($oldPayment->getRepeatCountLeft() - 1);
        $subscription->setRetryCount(5);

        return $subscription;
    }
}

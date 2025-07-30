<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Subscription;

use Magento\Customer\Model\Session;
use Nexi\Checkout\Api\Data\SubscriptionInterface;
use Nexi\Checkout\Model\ResourceModel\Subscription\Collection as SubscriptionCollection;
use Nexi\Checkout\Model\ResourceModel\Subscription\CollectionFactory;

class CustomerSubscriptionProvider
{
    /**
     * CustomerSubscriptionProvider constructor.
     *
     * @param CollectionFactory $subscriptionCollectionFactory
     * @param Session $customerSession
     */
    public function __construct(
        private CollectionFactory $subscriptionCollectionFactory,
        private Session $customerSession
    ) {
    }

    /**
     * Get customer subscriptions.
     *
     * @return SubscriptionCollection
     */
    public function getCustomerSubscriptions()
    {
        $collection = $this->subscriptionCollectionFactory->create();
        $collection->addFieldToFilter('main_table.status', ['active', 'pending_payment', 'failed', 'rescheduled']);

        $collection->getSelect()->join(
            ['link' => 'nexi_subscription_link'],
            'main_table.entity_id = link.subscription_id'
        )->columns('MAX(link.order_id) as max_id')
            ->group('link.subscription_id');

        $collection->getSelect()->join(
            ['so' => 'sales_order'],
            'link.order_id = so.entity_id',
            ['main_table.entity_id', 'so.base_grand_total']
        );
        $collection->getSelect()->join(
            ['rpp' => 'recurring_payment_profiles'],
            'main_table.recurring_profile_id = rpp.profile_id',
            'name'
        );

        $collection->addFieldToFilter('main_table.customer_id', $this->customerSession->getId());

        return $collection;
    }

    /**
     * Get customer closed subscriptions.
     *
     * @return SubscriptionCollection
     */
    public function getCustomerClosedSubscriptions()
    {
        $collection = $this->subscriptionCollectionFactory->create();
        $collection->addFieldToFilter('main_table.status', SubscriptionInterface::STATUS_CLOSED);

        $collection->getSelect()->join(
            ['link' => 'nexi_subscription_link'],
            'main_table.entity_id = link.subscription_id'
        )->columns('MAX(link.order_id) as max_id')
            ->group('link.subscription_id');

        $collection->getSelect()->join(
            ['so' => 'sales_order'],
            'link.order_id = so.entity_id',
            ['main_table.entity_id', 'so.base_grand_total']
        );
        $collection->getSelect()->join(
            ['rpp' => 'recurring_payment_profiles'],
            'main_table.recurring_profile_id = rpp.profile_id',
            'name'
        );

        $collection->addFieldToFilter('main_table.customer_id', $this->customerSession->getId());

        return $collection;
    }
}

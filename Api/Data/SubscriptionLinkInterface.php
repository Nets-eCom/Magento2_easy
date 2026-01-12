<?php
declare(strict_types=1);

namespace Nexi\Checkout\Api\Data;

interface SubscriptionLinkInterface
{
    public const FIELD_LINK_ID = 'link_id';
    public const FIELD_ORDER_ID = 'order_id';
    public const FIELD_SUBSCRIPTION_ID = 'subscription_id';

    /**
     * Get ID.
     *
     * @return int
     */
    public function getId();

    /**
     * Get order ID.
     *
     * @return string
     */
    public function getOrderId();

    /**
     * Get subscription ID.
     *
     * @return string
     */
    public function getSubscriptionId();

    /**
     * Set ID.
     *
     * @param string $linkId
     * @return $this
     */
    public function setId($linkId): self;

    /**
     * Set order ID.
     *
     * @param string $orderId
     * @return $this
     */
    public function setOrderId($orderId): self;

    /**
     * Set subscription ID.
     *
     * @param string $subscriptionId
     * @return $this
     */
    public function setSubscriptionId($subscriptionId): self;
}

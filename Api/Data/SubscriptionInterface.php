<?php

namespace Nexi\Checkout\Api\Data;

interface SubscriptionInterface
{
    public const FIELD_ENTITY_ID            = 'entity_id';
    public const FIELD_CUSTOMER_ID          = 'customer_id';
    public const FIELD_STATUS               = 'status';
    public const FIELD_NEXT_ORDER_DATE      = 'next_order_date';
    public const FIELD_RECURRING_PROFILE_ID = 'recurring_profile_id';
    public const FIELD_UPDATED_AT           = 'updated_at';
    public const FIELD_END_DATE             = 'end_date';
    public const FIELD_REPEAT_COUNT_LEFT    = 'repeat_count_left';
    public const FIELD_RETRY_COUNT          = 'retry_count';
    public const FIELD_NEXI_SUBSCRIPTION_ID          = 'nexi_subscription_id';
    public const STATUS_PENDING_PAYMENT     = 'pending_payment';
    public const STATUS_ACTIVE              = 'active';
    public const STATUS_CLOSED              = 'closed';
    public const STATUS_FAILED              = 'failed';
    public const STATUS_RESCHEDULED         = 'rescheduled';

    public const CLONEABLE_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_RESCHEDULED,
    ];

    /**
     * Get ID.
     *
     * @return int
     */
    public function getId();

    /**
     * Get customer ID.
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Get next order date.
     *
     * @return string
     */
    public function getNextOrderDate(): string;

    /**
     * Get recurring profile ID.
     *
     * @return int
     */
    public function getRecurringProfileId(): int;

    /**
     * Get updated at value.
     *
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * Get repeat count left value.
     *
     * @return int
     */
    public function getRepeatCountLeft(): int;

    /**
     * Get retry count value.
     *
     * @return int
     */
    public function getRetryCount(): int;

    /**
     * Get nexi subscription ID.
     *
     * @return string
     */
    public function getNexiSubscriptionId(): string;

    /**
     * Set ID.
     *
     * @param int $entityId
     *
     * @return $this
     */
    public function setId(int $entityId): self;

    /**
     * Set customer ID.
     *
     * @param int $customerId
     *
     * @return $this
     */
    public function setCustomerId(int $customerId): self;

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * Set next order date.
     *
     * @param string $date
     *
     * @return $this
     */
    public function setNextOrderDate(string $date): self;

    /**
     * Set recurring profile ID.
     *
     * @param int $profileId
     *
     * @return $this
     */
    public function setRecurringProfileId(int $profileId): self;

    /**
     * Set updated at value.
     *
     * @param string $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;

    /**
     * Set repeat count left value. How many times payment will be processed before it ends.
     *
     * @param int $count
     *
     * @return $this
     */
    public function setRepeatCountLeft(int $count): self;

    /**
     * Set retry count value. How many times a failed payment has been retried.
     *
     * @param int $count
     *
     * @return $this
     */
    public function setRetryCount(int $count): self;

    /**
     * Set nexi subscription ID.
     *
     * @param string $subscriptionId
     *
     * @return self
     */
    public function setNexiSubscriptionId(string $subscriptionId): self;
}

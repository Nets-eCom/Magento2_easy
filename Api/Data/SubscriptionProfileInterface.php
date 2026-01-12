<?php
declare(strict_types=1);

namespace Nexi\Checkout\Api\Data;

interface SubscriptionProfileInterface
{
    public const FIELD_PROFILE_ID = 'profile_id';
    public const FIELD_NAME = 'name';
    public const FIELD_DESCRIPTION = 'description';
    public const FIELD_SCHEDULE = 'schedule';

    /**
     * Get ID.
     *
     * @return int
     */
    public function getId();

    /**
     * Get name.
     *
     * @return string
     */
    public function getName();

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Get schedule.
     *
     * @return string
     */
    public function getSchedule();

    /**
     * Set ID.
     *
     * @param int $profileId
     * @return $this
     */
    public function setId($profileId): self;

    /**
     * Set name.
     *
     * @param string $name
     * @return $this
     */
    public function setName($name): self;

    /**
     * Set description.
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description): self;

    /**
     * Set schedule.
     *
     * @param string $schedule
     * @return $this
     */
    public function setSchedule($schedule): self;
}

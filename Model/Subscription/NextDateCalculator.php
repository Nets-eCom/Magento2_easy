<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Subscription;

use Carbon\Carbon;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Nexi\Checkout\Api\Data\SubscriptionProfileInterface;
use Nexi\Checkout\Api\SubscriptionProfileRepositoryInterface;

class NextDateCalculator
{
    /**
     * @var SubscriptionProfileInterface[]
     */
    private $profiles = [];

    /**
     * @var bool
     */
    private bool $forceWeekdays;

    /**
     * NextDateCalculator constructor.
     *
     * @param SubscriptionProfileRepositoryInterface $profileRepositoryInterface
     * @param SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private SubscriptionProfileRepositoryInterface $profileRepositoryInterface,
        private SerializerInterface                    $serializer,
        private ScopeConfigInterface                   $scopeConfig
    ) {
    }

    /**
     * Calculate next date for a profile.
     *
     * @param int $profileId
     * @param string $startDate
     *
     * @return Carbon
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function getNextDate($profileId, $startDate = 'now'): Carbon
    {
        $profile = $this->getProfileById($profileId);

        return $this->calculateNextDate($profile->getSchedule(), $startDate);
    }

    /**
     * Calculate the number of days interval until the next date for a profile.
     *
     * @param int $profileId
     * @param string $startDate
     *
     * @return int
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function getDaysInterval($profileId, $startDate = 'now'): int
    {
        $nextDate = $this->getNextDate($profileId, $startDate);

        return (int)abs($nextDate->diffInDays($startDate));
    }

    /**
     * Calculate next date based on the schedule.
     *
     * @param string $schedule
     * @param string $startDate
     *
     * @return Carbon
     * @throws Exception
     */
    private function calculateNextDate($schedule, $startDate): Carbon
    {
        $schedule   = $this->serializer->unserialize($schedule);
        $carbonDate = $startDate === 'now' ? Carbon::now() : Carbon::createFromFormat('Y-m-d H:i:s', $startDate);

        switch ($schedule['unit']) {
            case 'D':
                $nextDate = $carbonDate->addDays((int)$schedule['interval']);
                break;
            case 'W':
                $nextDate = $carbonDate->addWeeks((int)$schedule['interval']);
                break;
            case 'M':
                $nextDate = $this->addMonthsNoOverflow($carbonDate, $schedule['interval']);
                break;
            case 'Y':
                $nextDate = $carbonDate->addYearsNoOverflow($schedule['interval']);
                break;
            default:
                throw new LocalizedException(__('Schedule type not supported'));
        }

        if ($this->isForceWeekdays()) {
            $nextDate = $this->getNextWeekday($nextDate);
        }

        return $nextDate;
    }

    /**
     * Get profile by id
     *
     * @param int $profileId
     *
     * @return SubscriptionProfileInterface
     * @throws NoSuchEntityException
     */
    private function getProfileById($profileId): SubscriptionProfileInterface
    {
        if (!isset($this->profiles[$profileId])) {
            $this->profiles[$profileId] = $this->profileRepositoryInterface->get($profileId);
        }

        return $this->profiles[$profileId];
    }

    /**
     * Get force weekdays config
     *
     * @return bool
     */
    private function isForceWeekdays(): bool
    {
        if (!isset($this->forceWeekdays)) {
            $this->forceWeekdays = $this->scopeConfig->isSetFlag('sales/recurring_payment/force_weekdays');
        }
        return $this->forceWeekdays;
    }

    /**
     * Get next weekday
     *
     * @param Carbon $nextDate
     *
     * @return Carbon
     */
    private function getNextWeekday($nextDate): Carbon
    {
        $newCarbonDate = new Carbon($nextDate);
        if (!$newCarbonDate->isWeekday()) {
            $newCarbonDate = $newCarbonDate->nextWeekday();
            if ($nextDate->format('m') != $newCarbonDate->format('m')) {
                $newCarbonDate = $newCarbonDate->previousWeekday();
            }
        }
        return $newCarbonDate;
    }

    /**
     * Add months no overflow
     *
     * @param Carbon $carbonDate
     * @param int $interval
     *
     * @return Carbon
     */
    private function addMonthsNoOverflow($carbonDate, $interval): Carbon
    {
        $isLastOfMonth = $carbonDate->isLastOfMonth();
        $nextDate      = $carbonDate->addMonthsNoOverflow($interval);

        // adjust date to match the last day of month if the previous date was also last date of month.
        if ($isLastOfMonth) {
            $nextDate->endOfMonth();
        }

        return $nextDate;
    }
}

<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Subscription;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class TotalConfigProvider implements ConfigProviderInterface
{
    private const NO_SCHEDULE_VALUE = null;
    private const IS_SUBSCRIPTIONS_ENABLED = 'nexi/subscriptions/active_subscriptions';

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * TotalConfigProvider constructor.
     *
     * @param Session $checkoutSession
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Is recurring payment feature enable.
     *
     * @return bool
     */
    public function isSubscriptionsEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::IS_SUBSCRIPTIONS_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get subscription values to config.
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        return [
            'isRecurringScheduled' => $this->isSubscriptionScheduled(),
            'recurringSubtotal' => $this->getSubscriptionSubtotal()
            ];
    }

    /**
     * Is cart has subscription schedule products.
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function isSubscriptionScheduled(): bool
    {
        $quoteItems = $this->checkoutSession->getQuote()->getAllItems();
        if ($quoteItems) {
            foreach ($quoteItems as $item) {
                if ($item->getProduct()->getCustomAttribute('recurring_payment_schedule') != self::NO_SCHEDULE_VALUE) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get subscription cart subtotal value.
     *
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getSubscriptionSubtotal(): float
    {
        if ($this->isSubscriptionsEnabled()) {
            $recurringSubtotal = 0.00;
            if ($this->isSubscriptionScheduled()) {
                $quoteItems = $this->checkoutSession->getQuote()->getAllItems();
                foreach ($quoteItems as $item) {
                    if ($item->getProduct()
                            ->getCustomAttribute('recurring_payment_schedule') != self::NO_SCHEDULE_VALUE) {
                        $recurringSubtotal = $recurringSubtotal + ($item->getPrice() * $item->getQty());
                    }
                }
            }

            return $recurringSubtotal;
        }

        return 0.00;
    }
}

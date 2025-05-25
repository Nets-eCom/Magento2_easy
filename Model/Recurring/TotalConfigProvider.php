<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Recurring;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class TotalConfigProvider implements ConfigProviderInterface
{
    private const NO_SCHEDULE_VALUE = null;
    private const IS_RECURRING_PAYMENT_ENABLED = 'sales/recurring_payment/active_recurring_payment';

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
    public function isRecurringPaymentEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::IS_RECURRING_PAYMENT_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get recurring payment values to config.
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        return [
            'isRecurringScheduled' => $this->isRecurringScheduled(),
            'recurringSubtotal' => $this->getRecurringSubtotal()
            ];
    }

    /**
     * Is cart has recurring payment schedule products.
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function isRecurringScheduled(): bool
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
     * Get recurring-payment cart subtotal value.
     *
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getRecurringSubtotal(): float
    {
        if ($this->isRecurringPaymentEnabled()) {
            $recurringSubtotal = 0.00;
            if ($this->isRecurringScheduled()) {
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

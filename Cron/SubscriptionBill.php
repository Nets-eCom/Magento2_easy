<?php
declare(strict_types=1);

namespace Nexi\Checkout\Cron;

use Nexi\Checkout\Model\Subscription\Bill;
use Nexi\Checkout\Model\Subscription\TotalConfigProvider;

class SubscriptionBill
{
    /**
     * SubscriptionBill constructor.
     *
     * @param Bill $bill
     * @param TotalConfigProvider $totalConfigProvider config provider
     */
    public function __construct(
        private Bill $bill,
        private TotalConfigProvider $totalConfigProvider
    ) {
    }

    /**
     * Execute
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        if ($this->totalConfigProvider->isSubscriptionsEnabled()) {
            $this->bill->process();
        }
    }
}

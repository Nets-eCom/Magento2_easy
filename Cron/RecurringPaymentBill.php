<?php

namespace Nexi\Checkout\Cron;

use Nexi\Checkout\Model\Recurring\Bill;
use Nexi\Checkout\Model\Recurring\TotalConfigProvider;

class RecurringPaymentBill
{
    /**
     * RecurringPaymentBill constructor.
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
        if ($this->totalConfigProvider->isRecurringPaymentEnabled()) {
            $this->bill->process();
        }
    }
}

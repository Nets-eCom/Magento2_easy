<?php

namespace Nexi\Checkout\Cron;

use Nexi\Checkout\Model\Recurring\Notify;
use Nexi\Checkout\Model\Recurring\TotalConfigProvider;

class RecurringPaymentNotify
{
    /**
     * RecurringPaymentNotify constructor.
     *
     * @param Notify $notify
     * @param TotalConfigProvider $totalConfigProvider
     */
    public function __construct(
        private Notify $notify,
        private TotalConfigProvider $totalConfigProvider
    ) {
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        if ($this->totalConfigProvider->isRecurringPaymentEnabled()) {
            $this->notify->process();
        }
    }
}

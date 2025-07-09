<?php

namespace Nexi\Checkout\Cron;

use Nexi\Checkout\Model\Subscription\Notify;
use Nexi\Checkout\Model\Subscription\TotalConfigProvider;

class SubscriptionNotify
{
    /**
     * SubscriptionNotify constructor.
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
        if ($this->totalConfigProvider->isSubscriptionsEnabled()) {
            $this->notify->process();
        }
    }
}

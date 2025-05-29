<?php

namespace Nexi\Checkout\Plugin;

use Magento\Framework\Event\Observer;
use Magento\Quote\Observer\SubmitObserver;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Config\Config;

class SendEmailPlugin
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * Don't send email at that point if embedded checkout is enabled.
     *
     * @param SubmitObserver $subject
     * @param Observer $observer
     *
     * @return array
     */
    public function beforeExecute(SubmitObserver $subject, Observer $observer): array
    {
        if ($this->config->isEmbedded()) {
            $observer->getEvent()->getOrder()->setCanSendNewEmailFlag(false);
        }

        return [$observer];
    }
}

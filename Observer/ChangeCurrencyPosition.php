<?php

namespace Dibs\EasyCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;

class ChangeCurrencyPosition implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $currencyOptions = $observer->getEvent()->getCurrencyOptions();
        $currencyOptions->setData('position', \Magento\Framework\Currency::RIGHT);

        return $this;
    }
}
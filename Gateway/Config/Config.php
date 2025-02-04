<?php

namespace Nexi\Checkout\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as MagentoConfig;
use Nexi\Checkout\Model\Config\Source\Environment;

class Config extends MagentoConfig
{
    public const CODE = 'nexi';

    public function getEnvironment(): ?string
    {
        return $this->getValue('environment');
    }

    public function isLiveMode(): bool
    {
        return $this->getEnvironment() === Environment::LIVE;
    }

    public function isActive(): bool
    {
        return (bool) $this->getValue('active');
    }

    public function getApiKey(): ?string
    {
        return $this->getValue('api_key');
    }

    public function getApiIdentifier()
    {
        return $this->getValue('api_identifier');
    }

    public function getWebshopTermsAndConditionsUrl()
    {
        return $this->getValue('webshop_terms_and_conditions_url');
    }

    public function getPaymentsTermsAndConditionsUrl()
    {
        return $this->getValue('payments_terms_and_conditions_url');
    }
}

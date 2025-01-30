<?php

namespace Nexi\Checkout\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    public const CODE = 'nexi';
    public const KEY_ACTIVE = 'active';
    public const API_KEY = 'api_key';
    public const API_IDENTIFIER = 'api_identifier';
    public const KEY_ENVIRONMENT = 'environment';

    public function isActive(): bool
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    public function getApiKey()
    {
        return $this->getValue(self::API_KEY);
    }

    public function getApiIdentifier()
    {
        return $this->getValue(self::API_IDENTIFIER);
    }

    public function getEnvironment(): string
    {
        return $this->getValue(self::KEY_ENVIRONMENT);
    }
}

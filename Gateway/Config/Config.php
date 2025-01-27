<?php

namespace Nexi\Checkout\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    public const CODE = 'nexi';

    public const KEY_ENVIRONMENT = 'environment';
    public const KEY_ACTIVE = 'active';
    public const KEY_CLIENT_TOKEN = 'client_token';

    public function getEnvironment(): string
    {
        return $this->getValue(self::KEY_ENVIRONMENT);
    }

    public function isActive(): bool
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    public function getClientToken()
    {
        return $this->getValue('client_token');
    }
}

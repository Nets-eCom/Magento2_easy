<?php

namespace Nexi\Checkout\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as MagentoConfig;
use Nexi\Checkout\Model\Config\Source\Environment;
use NexiCheckout\Factory\Provider\HttpClientConfigurationProvider;

class Config extends MagentoConfig
{
    public function __construct(
        ScopeConfigInterface                             $scopeConfig,
        $methodCode = null,
        $pathPattern = MagentoConfig::DEFAULT_PATH_PATTERN,
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    public const CODE = 'nexi';

    public const KEY_ENVIRONMENT = 'environment';
    public const KEY_SECRET_KEY  = 'secret_key';
    public const KEY_ACTIVE      = 'active';
    public const API_IDENTIFIER  = 'api_identifier';

    public function getEnvironment(): string
    {
        return $this->getValue(self::KEY_ENVIRONMENT);
    }

    public function isLiveMode(): bool
    {
        return $this->getEnvironment() === Environment::LIVE;
    }

    public function isActive(): bool
    {
        return (bool)$this->getValue(self::KEY_ACTIVE);
    }

    public function getSecretKey(): string
    {
        return $this->getValue(self::KEY_SECRET_KEY);
    }
}

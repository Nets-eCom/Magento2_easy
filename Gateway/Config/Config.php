<?php

namespace Nexi\Checkout\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as MagentoConfig;
use Nexi\Checkout\Model\Config\Source\Environment;

class Config extends MagentoConfig
{
    public const CODE = 'nexi';
    public const KEY_ENVIRONMENT = 'environment';
    public const KEY_ACTIVE = 'active';
    public const API_KEY = 'api_key';
    public const API_IDENTIFIER = 'api_identifier';
    public const WEBSHOP_TERMS_AND_CONDITIONS = 'webshop_terms_and_conditions_url';
    public const PAYMENT_TERMS_AND_CONDITIONS = 'payment_terms_and_conditions_url';

    /**
     * Gateway Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param string $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        string $methodCode = self::CODE,
        string $pathPattern = MagentoConfig::DEFAULT_PATH_PATTERN,
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    /**
     * GetEnvironment method.
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->getValue(self::KEY_ENVIRONMENT);
    }

    /**
     * IsLiveMode method.
     *
     * @return bool
     */
    public function isLiveMode(): bool
    {
        return $this->getEnvironment() === Environment::LIVE;
    }

    /**
     * IsActive method.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool)$this->getValue(self::KEY_ACTIVE);
    }

    /**
     * GetApiKey method.
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->getValue(self::API_KEY);
    }

    /**
     * GetApiIdentifier method.
     *
     * @return string
     */
    public function getApiIdentifier(): string
    {
        return $this->getValue(self::API_IDENTIFIER);
    }

    /**
     * GetWebshopTermsAndConditions method.
     *
     * @return string
     */
    public function getWebshopTermsAndConditions(): string
    {
        return $this->getValue(self::WEBSHOP_TERMS_AND_CONDITIONS);
    }

    /**
     * GetPaymentTermsAndConditions method.
     *
     * @return string
     */
    public function getPaymentTermsAndConditions(): string
    {
        return $this->getValue(self::PAYMENT_TERMS_AND_CONDITIONS);
    }
}

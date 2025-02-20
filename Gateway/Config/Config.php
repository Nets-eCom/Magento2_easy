<?php

namespace Nexi\Checkout\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as MagentoConfig;
use Nexi\Checkout\Model\Config\Source\Environment;

class Config extends MagentoConfig
{
    public const CODE = 'nexi';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param $methodCode
     * @param $pathPattern
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
                                              $methodCode = null,
                                              $pathPattern = MagentoConfig::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    /**
     * @return string|null
     */
    public function getEnvironment(): ?string
    {
        return $this->getValue('environment');
    }

    /**
     * @return bool
     */
    public function isLiveMode(): bool
    {
        return $this->getEnvironment() === Environment::LIVE;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool)$this->getValue('active');
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->getValue('api_key');
    }

    /**
     * @return mixed|null
     */
    public function getApiIdentifier()
    {
        return $this->getValue('api_identifier');
    }

    /**
     * @return string
     */
    public function getWebshopTermsAndConditionsUrl(): string
    {
        return (string)$this->getValue('webshop_terms_and_conditions_url');
    }

    /**
     * @return string
     */
    public function getPaymentsTermsAndConditionsUrl(): string
    {
        return (string)$this->getValue('payment_terms_and_conditions_url');
    }

    /**
     * @return string
     */
    public function getIntegrationType(): string
    {
        return $this->getValue('integration_type');
    }

    /**
     * @return string
     */
    public function getWebhookSecret(): string
    {
        return $this->getValue('webhook_secret');
    }

    /**
     * authorize, authorize_capture
     *
     * @return string
     */
    public function getPaymentAction(): string
    {
        return $this->getValue('payment_action');
    }

    /**
     * @return mixed|null
     */
    public function getMerchantHandlesConsumerData()
    {
        return $this->getValue('merchant_handles_consumer_data');
    }

    /**
     * @return mixed
     */
    public function getCountryCode()
    {
        return $this->scopeConfig->getValue('general/country/default');
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as MagentoConfig;
use Magento\Payment\Model\MethodInterface;
use Nexi\Checkout\Model\Config\Source\Environment;

class Config extends MagentoConfig
{
    public const CODE = 'nexi';

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        $methodCode = null,
        $pathPattern = MagentoConfig::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    /**
     * Get the environment
     *
     * @return string|null
     */
    public function getEnvironment(): ?string
    {
        return $this->getValue('environment');
    }

    /**
     * Check if the environment is live
     *
     * @return bool
     */
    public function isLiveMode(): bool
    {
        return $this->getEnvironment() === Environment::LIVE;
    }

    /**
     * Is the payment method active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool)$this->getValue('active');
    }

    /**
     * Get secret key
     *
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        if ($this->isLiveMode()) {
            return $this->getValue('secret_key');
        }

        return $this->getTestApiKey();
    }

    /**
     * Get test secret key
     *
     * @return mixed|null
     */
    public function getTestApiKey()
    {
        return $this->getValue('test_secret_key');
    }

    /**
     * Get api identifier
     *
     * @return mixed|null
     */
    public function getCheckoutKey()
    {
        if ($this->isLiveMode()) {
            return $this->getValue('checkout_key');
        }

        return $this->getValue('test_checkout_key');
    }

    /**
     * Get webshop terms and conditions url
     *
     * @return string
     */
    public function getWebshopTermsAndConditionsUrl(): string
    {
        return (string)$this->getValue('webshop_terms_and_conditions_url');
    }

    /**
     * Get payments terms and conditions url
     *
     * @return string
     */
    public function getPaymentsTermsAndConditionsUrl(): string
    {
        return (string)$this->getValue('payment_terms_and_conditions_url');
    }

    /**
     * Get integration type
     *
     * @return string
     */
    public function getIntegrationType(): string
    {
        return $this->getValue('integration_type');
    }

    /**
     * Get webhook secret
     *
     * @return string
     */
    public function getWebhookSecret(): string
    {
        return $this->getValue('webhook_secret');
    }

    /**
     * Get payment action: authorize, authorize_capture
     *
     * @return string
     */
    public function getPaymentAction(): string
    {
        return $this->getValue('is_auto_capture') ?
            MethodInterface::ACTION_AUTHORIZE_CAPTURE :
            MethodInterface::ACTION_AUTHORIZE;
    }

    /**
     * Get if the merchant handles consumer data
     *
     * @return mixed|null
     */
    public function getMerchantHandlesConsumerData()
    {
        return $this->getValue('merchant_handles_consumer_data');
    }

    /**
     * Get the country code
     *
     * @return mixed
     */
    public function getCountryCode()
    {
        return $this->scopeConfig->isSetFlag('general/country/default');
    }
}

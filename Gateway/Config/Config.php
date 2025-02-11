<?php

namespace Nexi\Checkout\Gateway\Config;

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
        return (bool)$this->getValue('active');
    }

    public function getApiKey(): ?string
    {
        return $this->getValue('api_key');
    }

    public function getApiIdentifier()
    {
        return $this->getValue('api_identifier');
    }

    public function getWebshopTermsAndConditionsUrl(): string
    {
        return (string)$this->getValue('webshop_terms_and_conditions_url');
    }

    public function getPaymentsTermsAndConditionsUrl(): string
    {
        return (string)$this->getValue('payment_terms_and_conditions_url');
    }

    public function getIntegrationType(): string
    {
        return $this->getValue('integration_type');
    }

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
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Repository;
use Nexi\Checkout\Gateway\Config\Config;
use Magento\Payment\Helper\Data as PaymentHelper;
use Nexi\Checkout\Gateway\PaymentMethodsService;
use Nexi\Checkout\Model\Config\Source\Environment;
use Nexi\Checkout\Model\Config\Source\PaymentTypesEnum;
use Nexi\Checkout\Model\SplitPayment\PaymentMethodsFetcherInterface;

class ConfigProvider implements ConfigProviderInterface
{
    private const ENV_LIVE_JS_URL = 'NEXI_CHECKOUT_JS_LIVE_URL';
    private const ENV_TEST_JS_URL = 'NEXI_CHECKOUT_JS_TEST_URL';
    private const DEFAULT_LIVE_JS_URL = 'https://checkout.dibspayment.eu/v1/checkout.js?v=1';
    private const DEFAULT_TEST_JS_URL = 'https://test.checkout.dibspayment.eu/v1/checkout.js?v=1';

    public function __construct(
        private readonly Config $config,
        private readonly PaymentHelper $paymentHelper,
        private readonly Repository $assetRepo,
        private readonly PaymentMethodsFetcherInterface $paymentMethodsFetcher,
        private readonly Session $checkoutSession,
    ) {
    }

    /**
     * Returns Nexi configuration values.
     *
     * @return array
     */
    public function getConfig(): array
    {
        if (!$this->config->isActive()) {
            return [];
        }

        $config = [
            'payment' => [
                Config::CODE => [
                    'isActive'         => $this->config->isActive(),
                    'label'            => $this->paymentHelper->getMethodInstance(Config::CODE)->getTitle(),
                    'integrationType'  => $this->config->getIntegrationType(),
                    'payTypeSplitting' => $this->config->getPayTypeSplitting(),
                    'subselections'    => $this->getSubselections(),
                    'methodIcons'      => $this->getMethodIcons(),
                ]
            ]
        ];

        if ($this->config->isEmbedded()) {
            $config['payment'][Config::CODE]['checkoutKey'] = $this->config->getCheckoutKey();
            $config['payment'][Config::CODE]['checkoutJsUrl'] = $this->config->getEnvironment() === Environment::LIVE
                ? $this->getEnvOrDefault(self::ENV_LIVE_JS_URL, self::DEFAULT_LIVE_JS_URL)
                : $this->getEnvOrDefault(self::ENV_TEST_JS_URL, self::DEFAULT_TEST_JS_URL);
        }

        return $config;
    }

    /**
     * Get subselections for payment types.
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getSubselections(): array
    {
        if (!$this->config->getPayTypeSplitting()) {
            return [];
        }

        $subselections  = [];
        $payTypeOptions = $this->config->getPayTypeOptions();

        $currency = null;
        if ($this->checkoutSession->hasQuote()) {
            $quote = $this->checkoutSession->getQuote();
            if ($quote->getId()) {
                $currency = $quote->getQuoteCurrencyCode();
            }
        }

        $availablePaymentMethods = $this->paymentMethodsFetcher->getAvailablePaymentMethods($currency);
        $availablePaymentTypes = array_fill_keys(array_column($availablePaymentMethods, 'value'), true);

        foreach ($payTypeOptions as $option) {
            if (isset($availablePaymentTypes[$option->value])) {
                $subselections[] = [
                    'value' => $option->value,
                    'label' => __($option->value),
                ];
            }
        }

        return $subselections;
    }

    /**
     * Get icons for payment methods.
     *
     * @return string[]
     */
    private function getMethodIcons(): array
    {
        $icons = [];
        foreach (PaymentTypesEnum::cases() as $paymentType) {
            $icon = $paymentType->icon();
            if ($icon === '') {
                continue;
            }

            $icons[$paymentType->value] = $this->assetRepo->getUrl('Nexi_Checkout::images/' . $icon);
        }

        return $icons;
    }

    private function getEnvOrDefault(string $envVar, string $default): string
    {
        $value = getenv($envVar);
        return ($value !== false) ? $value : $default;
    }
}

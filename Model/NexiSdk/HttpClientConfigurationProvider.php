<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\NexiSdk;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use NexiCheckout\Factory\Provider\HttpClientConfigurationProvider as VendorHttpClientConfigurationProvider;
use NexiCheckout\Factory\Provider\HttpClientConfigurationProviderInterface;
use NexiCheckout\Http\Configuration;

class HttpClientConfigurationProvider implements HttpClientConfigurationProviderInterface
{
    public const DEFAULT_LIVE_URL = 'https://api.dibspayment.eu';
    public const DEFAULT_TEST_URL = 'https://test.api.dibspayment.eu';

    private const COMMERCE_PLATFORM_TAG = 'Magento2';
    private const ENV_LIVE_URL = 'NEXI_CHECKOUT_API_LIVE_URL';
    private const ENV_TEST_URL = 'NEXI_CHECKOUT_API_TEST_URL';

    public function __construct(
        private readonly ProductMetadataInterface $productMetadata,
        private readonly ModuleListInterface $moduleList
    ) {
    }

    public function provide(string $secretKey, bool $isLiveMode): Configuration
    {
        $liveUrl = $this->getEnvOrDefault(self::ENV_LIVE_URL, self::DEFAULT_LIVE_URL);
        $testUrl = $this->getEnvOrDefault(self::ENV_TEST_URL, self::DEFAULT_TEST_URL);

        $configurationProvider = new VendorHttpClientConfigurationProvider(
            $liveUrl,
            $testUrl,
            $this->buildCommercePlatformTag()
        );

        return $configurationProvider->provide($secretKey, $isLiveMode);
    }

    private function getEnvOrDefault(string $envVar, string $default): string
    {
        $value = getenv($envVar);

        return ($value !== false) ? $value : $default;
    }

    private function buildCommercePlatformTag(): string
    {
        return sprintf(
            '%s %s, %s, php%s',
            self::COMMERCE_PLATFORM_TAG,
            $this->productMetadata->getVersion(),
            $this->moduleList->getOne('Nexi_Checkout')['setup_version'],
            PHP_VERSION
        );
    }
}

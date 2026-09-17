<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\SplitPayment;

use Magento\Framework\Cache\FrontendInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CachedPaymentMethodsFetcher implements PaymentMethodsFetcherInterface
{
    private const CACHE_TAG = 'nexi_checkout';
    private const CACHE_KEY_PREFIX = 'nexi_available_methods_';
    private const CACHE_LIFETIME = 60 * 60 * 12; // 12h

    public function __construct(
        private readonly PaymentMethodsFetcher $paymentMethodsFetcher,
        private readonly FrontendInterface $cache,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get available payment methods from cache if possible
     *
     * @inheritdoc
     */
    public function getAvailablePaymentMethods(?string $currency = null): array
    {
        $cacheKey = $this->getCacheKey($currency);

        if ($this->cache->test($cacheKey)) {
            $cachedResult = $this->cache->load($cacheKey);
            $result = json_decode($cachedResult, true);

            if ($result !== null) {
                return $result;
            }
        }

        $result = $this->paymentMethodsFetcher->getAvailablePaymentMethods($currency);

        if ($result === []) {
            return $result;
        }

        try {
            $this->cache->save(
                json_encode($result, JSON_THROW_ON_ERROR),
                $cacheKey,
                [self::CACHE_TAG],
                self::CACHE_LIFETIME
            );
        } catch (\JsonException $e) {
            $this->logger->error("Failed to cache payment methods: {$e->getMessage()}");
        }

        return $result;
    }

    public function clear(): void
    {
        $this->cache->clean(tags: [self::CACHE_TAG]);
    }

    private function getCacheKey(?string $currency): string
    {
        $currencyPart = $currency ? strtolower($currency) : 'null';
        $storeId = $this->storeManager->getStore()->getId() . '_';

        return self::CACHE_KEY_PREFIX . $storeId . $currencyPart;
    }
}

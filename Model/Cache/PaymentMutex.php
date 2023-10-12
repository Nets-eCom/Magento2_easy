<?php declare(strict_types=1);

namespace Dibs\EasyCheckout\Model\Cache;

class PaymentMutex
{
    /**
     * Lock prefix
     */
    private const CACHE_IDENTIFIER = 'PAYMENT_LOCK_';

    private \Magento\Framework\App\Cache\Type\Config $backendCache;

    /**
     * Mutex constructor.
     */
    public function __construct(\Magento\Framework\App\Cache\Type\Config $backendCache)
    {
        $this->backendCache = $backendCache;
    }

    /**
     * @param $pid
     */
    public function lock($pid) : void
    {
        $identifier = $this->getCacheIdentifier($pid);
        $this->backendCache->save(
            $pid,
            $identifier,
            [$identifier],
            2
        );
    }

    /**
     * @param $pid
     *
     * @return bool
     */
    public function test($pid)
    {
        return (bool) $this->backendCache->test($this->getCacheIdentifier($pid));
    }

    /**
     * @param $pid
     */
    public function release($pid) : void
    {
        $this->backendCache->remove($this->getCacheIdentifier($pid));
    }

    /**
     * @param $pid
     *
     * @return string
     */
    private function getCacheIdentifier($pid)
    {
        return self::CACHE_IDENTIFIER . $pid;
    }
}

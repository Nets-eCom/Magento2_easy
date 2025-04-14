<?php


declare(strict_types=1);

namespace Nexi\Checkout\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class GenerateWebhookSecret implements DataPatchInterface
{
    private const CONFIG_PATH_API_KEY = 'payment/nexi/webhook_secret';

    /**
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private readonly WriterInterface    $configWriter,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    /**
     * Generate secret key for webhook verification
     *
     * @return void
     */
    public function apply(): void
    {
        // Generate a random API key
        $randomApiKey = bin2hex(random_bytes(16)); // 32-character random key

        // Optionally encrypt the key before saving
        $encryptedApiKey = $this->encryptor->encrypt($randomApiKey);

        // Save the API key to the configuration
        $this->configWriter->save(self::CONFIG_PATH_API_KEY, $encryptedApiKey);
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}

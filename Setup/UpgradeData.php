<?php

namespace Dibs\EasyCheckout\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.4.6') < 0) {
            $this->migrateTestLiveKeys($setup);
        }
    }

    /**
     * Migrates secret and checkout key path depending on test and live value prefix
     *
     * @param ModuleDataSetupInterface $installer
     * @return void
     */
    private function migrateTestLiveKeys(ModuleDataSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $table = $installer->getTable('core_config_data');

        // UPDATE core_config_data SET path = 'dibs_easycheckout/connection/test_secret_key'
        // WHERE path = 'dibs_easycheckout/connection/secret_key' AND value LIKE 'test-secret-key-%';
        $connection->update(
            $table,
            ['path' => 'dibs_easycheckout/connection/test_secret_key'],
             "path = 'dibs_easycheckout/connection/secret_key' AND value LIKE 'test-secret-key-%'"
        );
        $connection->update(
            $table,
            ['path' => 'dibs_easycheckout/connection/test_checkout_key'],
            "path = 'dibs_easycheckout/connection/checkout_key' AND value LIKE 'test-checkout-key-%'"
        );

        $connection->update(
            $table,
            ['path' => 'dibs_easycheckout/connection/live_secret_key'],
            "path = 'dibs_easycheckout/connection/secret_key' AND value LIKE 'live-secret-key-%'"
        );
        $connection->update(
            $table,
            ['path' => 'dibs_easycheckout/connection/live_checkout_key'],
            "path = 'dibs_easycheckout/connection/checkout_key' AND value LIKE 'live-checkout-key-%'"
        );
    }
}

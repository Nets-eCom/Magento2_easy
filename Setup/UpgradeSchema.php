<?php

namespace Dibs\EasyCheckout\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $definition = [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '10,2',
            'default' => 0.00,
            'nullable' => true,
            'comment' =>'Dibs Invoice Fee'
        ];

        $tables  = ['quote_address','quote_address','quote','sales_order','sales_invoice','sales_creditmemo'];
        foreach ($tables as $table) {
            $setup->getConnection()->addColumn($setup->getTable($table), "dibs_invoice_fee", $definition);
        }

        if (version_compare($context->getVersion(), '1.1.4') < 0) {
            $this->addQuoteSignatureAttribute($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    private function addQuoteSignatureAttribute(SchemaSetupInterface $installer)
    {
        $installer->startSetup();
        $installer->getConnection()->addColumn(
            $installer->getTable('quote'),
            'hash_signature',
            [
                'type' => 'text',
                'nullable' => true,
                'comment' => 'Quote verification signature hash',
            ]
        );
    }
}

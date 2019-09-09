<?php

namespace Dibs\EasyCheckout\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

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

        $setup->endSetup();
    }
}
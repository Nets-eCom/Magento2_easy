<?php
/**
 * Copyright © Nexi. All rights reserved.
 */
declare(strict_types=1);

namespace Nexi\Checkout\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;

/**
 * Add payment_authorized order status for pending_payment state
 */
class AddPaymentAuthorizedOrderStatus implements DataPatchInterface
{
    public const STATUS_NEXI_AUTHORIZED = 'nexi_payment_authorized';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $data = [
            'status' => self::STATUS_NEXI_AUTHORIZED,
            'label'  => __('Payment Authorized')
        ];

        $this->moduleDataSetup->getConnection()->insertOnDuplicate(
            $this->moduleDataSetup->getTable('sales_order_status'),
            $data,
            ['status', 'label']
        );

        $data = [
            'status'           => self::STATUS_NEXI_AUTHORIZED,
            'state'            => Order::STATE_PENDING_PAYMENT,
            'is_default'       => 0,
            'visible_on_front' => 1
        ];

        $this->moduleDataSetup->getConnection()->insertOnDuplicate(
            $this->moduleDataSetup->getTable('sales_order_status_state'),
            $data,
            ['status', 'state', 'is_default', 'visible_on_front']
        );

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace Dibs\EasyCheckout\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Magento\Framework\Data\Collection;

class AddPaymentMethodToOrderGrid
{
    public function afterGetReport(CollectionFactory $subject, Collection $collection, string $requestName): Collection
    {
        if ($requestName !== 'sales_order_grid_data_source') {
            return $collection;
        }

        if ($collection->getMainTable() !== $collection->getResource()->getTable('sales_order_grid')) {
            return $collection;
        }

        $salesOrder = $collection->getResource()->getTable('sales_order');
        $collection->getSelect()->joinLeft(
            ['so' => $salesOrder],
            'so.entity_id = main_table.entity_id',
        );

        $collection->addFilterToMap('dibs_payment_method', 'so.dibs_payment_method');

        return $collection;
    }
}

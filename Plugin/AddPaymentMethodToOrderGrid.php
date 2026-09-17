<?php

declare(strict_types=1);

namespace Nexi\Checkout\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;
use Nexi\Checkout\Model\Order\NexiOrderFields;

class AddPaymentMethodToOrderGrid
{
    private const SALES_ORDER_GRID_REQUEST_NAME = 'sales_order_grid_data_source';
    private const SALES_ORDER_GRID_TABLE = 'sales_order_grid';
    private const SALES_ORDER_TABLE = 'sales_order';

    public function afterGetReport(
        CollectionFactory $subject,
        SearchResult $collection,
        string $requestName
    ): SearchResult {
        if (!$this->isApplicable($collection, $requestName)) {
            return $collection;
        }

        $salesOrder = $collection->getResource()->getTable(self::SALES_ORDER_TABLE);
        $collection->getSelect()->joinLeft(
            ['so' => $salesOrder],
            'so.entity_id = main_table.entity_id',
            [NexiOrderFields::NEXI_PAYMENT_METHOD => 'so.' . NexiOrderFields::NEXI_PAYMENT_METHOD]
        );

        $collection->addFilterToMap(
            NexiOrderFields::NEXI_PAYMENT_METHOD,
            'so.' . NexiOrderFields::NEXI_PAYMENT_METHOD
        );

        return $collection;
    }

    private function isApplicable(SearchResult $collection, string $requestName): bool
    {
        if ($requestName !== self::SALES_ORDER_GRID_REQUEST_NAME) {
            return false;
        }

        if (!$collection instanceof Collection) {
            return false;
        }

        if ($collection->getMainTable() !== $collection->getResource()->getTable(self::SALES_ORDER_GRID_TABLE)) {
            return false;
        }

        return true;
    }
}

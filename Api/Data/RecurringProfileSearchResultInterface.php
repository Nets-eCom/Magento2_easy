<?php

namespace Nexi\Checkout\Api\Data;

interface RecurringProfileSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get items.
     *
     * @return \Nexi\Checkout\Api\Data\RecurringProfileInterface[] Array of collection items.
     */
    public function getItems();

    /**
     * Set items.
     *
     * @param \Nexi\Checkout\Api\Data\RecurringProfileInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}

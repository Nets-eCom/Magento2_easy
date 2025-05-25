<?php
namespace Nexi\Checkout\Api\Data;

interface SubscriptionSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get items.
     *
     * @return \Nexi\Checkout\Api\Data\SubscriptionInterface[] Array of collection items.
     */
    public function getItems();

    /**
     * Set items.
     *
     * @param \Nexi\Checkout\Api\Data\SubscriptionInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}

<?php
declare(strict_types=1);

namespace Nexi\Checkout\Api\Data;

interface SubscriptionLinkSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get items.
     *
     * @return \Nexi\Checkout\Api\Data\SubscriptionLinkInterface[] Array of collection items.
     */
    public function getItems();

    /**
     * Set items.
     *
     * @param \Nexi\Checkout\Api\Data\SubscriptionLinkInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}

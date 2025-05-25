<?php

namespace Nexi\Checkout\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * @api
 */
interface CardManagementInterface
{
    /**
     * Initialize add card process.
     *
     * @return string
     */
    public function generateAddCardUrl(): string;

    /**
     * Delete unused card.
     *
     * @param string $cardId
     * @return bool
     * @throws LocalizedException
     */
    public function delete(string $cardId): bool;
}

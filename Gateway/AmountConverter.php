<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway;

use InvalidArgumentException;

class AmountConverter
{
    /**
     * Nexi api requires the amount to be in cents.
     *
     * @param float $amount
     *
     * @return int
     */
    public function convertToNexiAmount($amount): int
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Amount must be a numeric value.');
        }

        return (int)round($amount * 100);
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Api;

use Magento\Quote\Api\Data\PaymentInterface;

interface PaymentValidateInterface
{
    /**
     * Initialize Nexi payment
     *
     * @param string $cartId
     *
     * @return string
     */
    public function validate(string $cartId);
}

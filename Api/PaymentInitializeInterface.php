<?php

declare(strict_types=1);

namespace Nexi\Checkout\Api;

use Magento\Quote\Api\Data\PaymentInterface;

interface PaymentInitializeInterface
{
    /**
     * Initialize Nexi payment
     *
     * @param string $cartId
     * @param string $integrationType
     * @param PaymentInterface $quotePayment
     *
     * @return string
     */
    public function initialize(string $cartId, string $integrationType, PaymentInterface $quotePayment);
}

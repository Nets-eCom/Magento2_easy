<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\SplitPayment;

interface PaymentMethodsFetcherInterface
{
    /**
     * Get available payment methods
     *
     * @return array{
     *  value: string,
     *  label: string
     * }
     */
    public function getAvailablePaymentMethods(?string $currency = null): array;
}

<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Receipt;

class ProcessPayment
{
    private const PAYMENT_PROCESSING_CACHE_PREFIX = "nexi-processing-payment-";

    public function process($params, $session)
    {
        // TODO: Create nexi specific
    }

    private function processPayment($params, $session, $orderNo)
    {
        // TODO: Create Nexi specfic
    }
}

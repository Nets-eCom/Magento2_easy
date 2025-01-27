<?php

namespace Nexi\Checkout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

class CreatePaymentRequestBuilder implements BuilderInterface
{
    public function build(array $buildSubject): array
    {
        return [];
    }
}

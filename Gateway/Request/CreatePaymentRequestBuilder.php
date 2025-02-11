<?php

namespace Nexi\Checkout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Request\NexiCheckout\RequestFactory;

class CreatePaymentRequestBuilder implements BuilderInterface
{
    public function __construct(
        private readonly RequestFactory $requestFactory
    ) {
    }

    public function build(array $buildSubject): array
    {
        /** @var Order $order */
        $order = $buildSubject['payment']->getPayment()->getOrder();

        return [
            'nexi_method' => 'createPayment',
            'body'        => $this->requestFactory->createPayment($order),
        ];
    }
}

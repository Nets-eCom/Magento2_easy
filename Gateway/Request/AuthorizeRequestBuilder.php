<?php

namespace Nexi\Checkout\Gateway\Request;

use Nexi\Checkout\Gateway\Config\Config;
use Magento\Payment\Gateway\Request\BuilderInterface;
use NexiCheckout\Model\Request\Payment;
use NexiCheckout\Model\Request\Payment\HostedCheckout;
use NexiCheckout\Model\Request\Payment\Order;

class AuthorizeRequestBuilder implements BuilderInterface
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function build(array $buildSubject): array
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $buildSubject['payment']->getPayment()->getOrder();
        $nexiOrder = new Order(
            items   : [],
            currency: $order->getBaseCurrencyCode(),
            amount  : $buildSubject['amount'],
        );

        $checkout = new HostedCheckout(
            returnUrl:'nexi/hpp/returnaction',
            cancelUrl:'nexi/hpp/cancelaction',
            termsUrl:  $this->config->getValue('terms_url'),
        );

        $payment = new Payment(
            order: $nexiOrder,
            checkout: $checkout,
        );

        return [
            'nexi_method' => 'createPayment',
            'body' => $payment,
        ];
    }
}

<?php

namespace Nexi\Checkout\Gateway\Request;

use Magento\Framework\Url;
use Nexi\Checkout\Gateway\Config\Config;
use Magento\Payment\Gateway\Request\BuilderInterface;
use NexiCheckout\Model\Request\Payment;
use NexiCheckout\Model\Request\Payment\HostedCheckout;
use NexiCheckout\Model\Request\Payment\Order;

class AuthorizeRequestBuilder implements BuilderInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Url    $url
    ) {
    }

    public function build(array $buildSubject): array
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order     = $buildSubject['payment']->getPayment()->getOrder();
        $nexiOrder = new Order(
            items   : [], //todo: implement
            currency: $order->getBaseCurrencyCode(),
            amount  : $buildSubject['amount'],
        );

        $checkout = new HostedCheckout(   // todo: implement optional parameters
            returnUrl: $this->url->getUrl('nexi/hpp/returnaction'),
            cancelUrl: $this->url->getUrl('nexi/hpp/cancelaction'),
            termsUrl : $this->config->getPaymentsTermsAndConditionsUrl(),
        );

        $payment = new Payment(
            order   : $nexiOrder,
            checkout: $checkout,
        );

        return [
            'nexi_method' => 'createPayment',
            'body'        => $payment,
        ];
    }
}

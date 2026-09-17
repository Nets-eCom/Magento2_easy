<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request\NexiCheckout;

use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Config\Source\PaymentTypesEnum;
use NexiCheckout\Model\Request\Payment\MethodConfiguration;

class MethodConfigurationBuilder
{
    private const COMPOUND_SPLIT_PAYMENT_METHODS = [
        PaymentTypesEnum::GOOGLE_PAY->value => [
            PaymentTypesEnum::GOOGLE_PAY->value,
            PaymentTypesEnum::CARD->value
        ],
        PaymentTypesEnum::APPLE_PAY->value => [
            PaymentTypesEnum::APPLE_PAY->value,
            PaymentTypesEnum::CARD->value
        ],
    ];

    /**
     * MethodConfigurationBuilder constructor.
     *
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config,
    ) {
    }

    /**
     * Build the payment methods configuration from the order or quote.
     *
     * @param Quote|Order $salesObject
     *
     * @return MethodConfiguration[]
     */
    public function buildPaymentMethodsConfiguration(Quote|Order $salesObject): array
    {
        $subselection = $salesObject->getPayment()->getAdditionalInformation('subselection');

        if (!$this->config->getPayTypeSplitting() || !$subselection) {
            return [];
        }

        $enabledMethods = self::COMPOUND_SPLIT_PAYMENT_METHODS[$subselection] ?? [$subselection];

        $methodConfigurations = [];
        foreach ($enabledMethods as $enabledMethod) {
            $methodConfigurations[] = new MethodConfiguration(
                name   : $enabledMethod,
                enabled: true
            );
        }

        return $methodConfigurations;
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Nexi\Checkout\Gateway\Config\Config;
use Magento\Payment\Helper\Data as PaymentHelper;
use Nexi\Checkout\Model\Config\Source\PaymentTypesEnum;
use Nexi\Checkout\Model\Subscription\TotalConfigProvider;

const HTTPS_CHECKOUT_DIBSPAYMENT_EU_V_1_ICONS = 'https://checkout.dibspayment.eu/v1/icons/';
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Payment methods that could be used for subscription payment.
     */
    public const SUBSCRIPTION_PAYMENT_TYPES = [
        PaymentTypesEnum::VISA,
        PaymentTypesEnum::MASTERCARD,
        PaymentTypesEnum::AMERICAN_EXPRESS,
        PaymentTypesEnum::DANKORT,
        PaymentTypesEnum::FORBRUGSFORENINGEN
    ];


    /**
     * @param Config $config
     * @param PaymentHelper $paymentHelper
     * @param TotalConfigProvider $totalConfigProvider
     */
    public function __construct(
        private readonly Config $config,
        private readonly PaymentHelper $paymentHelper,
        private readonly TotalConfigProvider $totalConfigProvider
    ) {
    }

    /**
     * Returns Nexi configuration values.
     *
     * @return array|\array[][]
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        if (!$this->config->isActive()) {
            return [];
        }

        $config = [
            'payment' => [
                Config::CODE => [
                    'isActive'         => $this->config->isActive(),
                    'environment'      => $this->config->getEnvironment(),
                    'label'            => $this->paymentHelper->getMethodInstance(Config::CODE)->getTitle(),
                    'integrationType'  => $this->config->getIntegrationType(),
                    'payTypeSplitting' => $this->config->getPayTypeSplitting(),
                    'subselections'    => $this->getSubselections(),
                    'methodIcons'      => $this->getMethodIcons(),
                ]
            ]
        ];

        if ($this->config->isEmbedded()) {
            $config['payment'][Config::CODE]['checkoutKey'] = $this->config->getCheckoutKey();
        }

        return $config;
    }

    /**
     * Get subselections for payment types.
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getSubselections(): array
    {
        if (!$this->config->getPayTypeSplitting()) {
            return [];
        }

        $subselections  = [];
        $payTypeOptions = $this->config->getPayTypeOptions();

        if ($this->totalConfigProvider->isSubscriptionScheduled()) {
            $payTypeOptions = $this->filterSubselectionsForSubscription($payTypeOptions);
        }

        /** @var PaymentTypesEnum $option */
        foreach ($payTypeOptions as $option) {
            $subselections[] = [
                'value' => $option->value,
                'label' => __($option->value),
            ];
        }

        return $subselections;
    }

    /**
     * Filter payment types for subscription.
     *
     * @param array $payTypeOptions
     *
     * @return array
     */
    private function filterSubselectionsForSubscription(array $payTypeOptions): array
    {
        return array_filter($payTypeOptions, function ($type) {
            return in_array($type, self::SUBSCRIPTION_PAYMENT_TYPES, true);
        });
    }

    /**
     * Get icons for payment methods.
     *
     * @return string[]
     */
    public function getMethodIcons()
    {
        $icons = [
            PaymentTypesEnum::VISA->value                 => 'visa-blue.svg',
            PaymentTypesEnum::MASTERCARD->value           => 'mastercard_wtext.svg',
            PaymentTypesEnum::FORBRUGSFORENINGEN->value   => 'forbrugsforeningen.svg',
            PaymentTypesEnum::PAYPAL->value               => 'paypal-text.svg',
            PaymentTypesEnum::VIPPS->value                => 'vipps.svg',
            PaymentTypesEnum::MOBILE_PAY->value           => 'mobilepay.svg',
            PaymentTypesEnum::SWISH->value                => 'swish_secondary.svg',
            PaymentTypesEnum::RATE_PAY_INVOICE->value     => 'ratepay.svg',
            PaymentTypesEnum::RATE_PAY_INSTALLMENT->value => 'ratepay.svg',
            PaymentTypesEnum::RATE_PAY_SEPA->value        => 'ratepay.svg',
            PaymentTypesEnum::APPLE_PAY->value            => 'applepay.svg',
            PaymentTypesEnum::DANKORT->value              => 'dankort.svg',
            PaymentTypesEnum::KLARNA->value               => 'klarna.svg',
            PaymentTypesEnum::GOOGLE_PAY->value           => 'googlepay.svg',
            'default'                                     => 'easy-logo-blue_150px.svg',
        ];

        return array_map(function ($type) {
            return HTTPS_CHECKOUT_DIBSPAYMENT_EU_V_1_ICONS . $type;
        }, $icons);
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Repository;
use Nexi\Checkout\Gateway\Config\Config;
use Magento\Payment\Helper\Data as PaymentHelper;
use Nexi\Checkout\Model\Config\Source\PaymentTypesEnum;
use Nexi\Checkout\Model\Subscription\TotalConfigProvider;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Payment methods that could be used for subscription payment.
     */
    public const SUBSCRIPTION_PAYMENT_TYPES = [
        PaymentTypesEnum::CARD
    ];

    /**
     * @param Config $config
     * @param PaymentHelper $paymentHelper
     * @param TotalConfigProvider $totalConfigProvider
     * @param Repository $assetRepo
     */
    public function __construct(
        private readonly Config $config,
        private readonly PaymentHelper $paymentHelper,
        private readonly TotalConfigProvider $totalConfigProvider,
        private readonly Repository $assetRepo
    ) {
    }

    /**
     * Returns Nexi configuration values.
     *
     * @return array
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
    public function getMethodIcons(): array
    {
        $icons = [
            PaymentTypesEnum::CARD->value                 => 'nexi-cards.png',
            PaymentTypesEnum::PAYPAL->value               => 'paypal.png',
            PaymentTypesEnum::VIPPS->value                => 'vipps.png',
            PaymentTypesEnum::MOBILE_PAY->value           => 'mobilepay.png',
            PaymentTypesEnum::SWISH->value                => 'swish.png',
            PaymentTypesEnum::RATE_PAY_INVOICE->value     => 'ratepay.png',
            PaymentTypesEnum::RATE_PAY_INSTALLMENT->value => 'ratepay.png',
            PaymentTypesEnum::RATE_PAY_SEPA->value        => 'ratepay.png',
            PaymentTypesEnum::SOFORT->value               => 'sofort.png',
            PaymentTypesEnum::TRUSTLY->value              => 'trustly.png',
            PaymentTypesEnum::APPLE_PAY->value            => 'applepay.png',
            PaymentTypesEnum::KLARNA->value               => 'klarna.png',
            PaymentTypesEnum::GOOGLE_PAY->value           => 'googlepay.png',
        ];

        return array_map(function ($image) {
            return $this->assetRepo->getUrl('Nexi_Checkout::images/' . $image);
        }, $icons);
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Nexi\Checkout\Gateway\Config\Config;
use Magento\Payment\Helper\Data as PaymentHelper;
use Nexi\Checkout\Gateway\Request\PaymentTypesEnum;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @param Config $config
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        private readonly Config $config,
        private readonly PaymentHelper $paymentHelper,
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
                    'subselections'    => $this->getSubselections()
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
     */
    private function getSubselections(): array
    {
        if (!$this->config->getPayTypeSplitting()) {
            return [];
        }

        $subselections  = [];
        $payTypeOptions = explode(',', $this->config->getPayTypeOptions());
        foreach ($payTypeOptions as $value) {
            $subselections[] = [
                'value' => $value,
                'label' => __($value),
            ];
        }

        return $subselections;
    }
}

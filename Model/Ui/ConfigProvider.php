<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Nexi\Checkout\Gateway\Config\Config;
use Magento\Payment\Helper\Data as PaymentHelper;

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
    public function getConfig()
    {
        if (!$this->config->isActive()) {
            return [];
        }

        $config = [
            'payment' => [
                Config::CODE => [
                    'isActive'    => $this->config->isActive(),
                    'environment' => $this->config->getEnvironment(),
                    'label'       => $this->paymentHelper->getMethodInstance(Config::CODE)->getTitle(),
                    'integrationType' => $this->config->getIntegrationType(),
                    'payTypeSplitting' => $this->config->getPayTypeSplitting()
                ]
            ]
        ];

        if ($this->config->isEmbedded()) {
            $config['payment'][Config::CODE]['checkoutKey'] = $this->config->getCheckoutKey();
        }

        return $config;
    }
}

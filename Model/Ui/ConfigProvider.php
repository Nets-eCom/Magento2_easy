<?php

namespace Nexi\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Nexi\Checkout\Gateway\Config\Config;
use Magento\Payment\Helper\Data as PaymentHelper;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * ConfigProvider constructor.
     *
     * @param Config $config
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        private readonly Config        $config,
        private readonly PaymentHelper $paymentHelper,
    ) {
    }

    /**
     * Returns Nexi configuration values.
     *
     * @return array|\array[][]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConfig()
    {
        if (!$this->config->isActive()) {
            return [];
        }

        return [
            'payment' => [
                Config::CODE => [
                    'isActive'    => $this->config->isActive(),
                    'environment' => $this->config->getEnvironment(),
                    'label'       => $this->paymentHelper->getMethodInstance(Config::CODE)->getTitle(),
                    'integrationType' => $this->config->getIntegrationType(),
                ]
            ]
        ];
    }
}

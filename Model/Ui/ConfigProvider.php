<?php

namespace Nexi\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Nexi\Checkout\Gateway\Config\Config;
use Magento\Payment\Helper\Data as PaymentHelper;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'nexi';

    public function __construct(
        private readonly Config        $config,
        private readonly PaymentHelper $paymentHelper,
    ) {
    }

    public function getConfig()
    {
        if (!$this->config->isActive()) {
            return [];
        }

        return [
            'payment' => [
                self::CODE => [
                    'isActive'    => $this->config->isActive(),
                    'clientToken' => $this->config->getClientToken(),
                    'environment' => $this->config->getEnvironment(),
                    'label'       => $this->paymentHelper->getMethodInstance(self::CODE)->getTitle(),
                ]
            ]
        ];
    }
}

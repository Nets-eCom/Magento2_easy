<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;

class IntegrationType implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray() : array
    {
        return [
            [
                'value' => IntegrationTypeEnum::EmbeddedCheckout->name,
                'label' => __('Embedded Checkout'),
            ],
            [
                'value' => IntegrationTypeEnum::HostedPaymentPage->name,
                'label' => __('Hosted Checkout'),
            ]
        ];
    }
}

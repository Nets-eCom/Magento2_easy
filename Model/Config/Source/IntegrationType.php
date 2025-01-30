<?php

namespace Nexi\Checkout\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class IntegrationType implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'hosted', 'label' => __('Hosted')],
            ['value' => 'embedded', 'label' => __('Embedded')]
        ];
    }
}

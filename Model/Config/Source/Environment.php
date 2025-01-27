<?php

namespace Nexi\Checkout\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'test', 'label' => __('Test')],
            ['value' => 'live', 'label' => __('Live')]
        ];
    }
}

<?php

namespace Nexi\Checkout\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{
    const TEST = 'test';
    const LIVE = 'live';

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::TEST, 'label' => __('Test')],
            ['value' => self::LIVE, 'label' => __('Live')]
        ];
    }
}

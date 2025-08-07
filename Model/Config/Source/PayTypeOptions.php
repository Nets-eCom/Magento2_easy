<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PayTypeOptions implements OptionSourceInterface
{
    /**
     * Return an array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach (PaymentTypesEnum::cases() as $case) {
            $options[] = [
                'value' => $case->value,
                'label' => __($case->value)
            ];
        }
        return $options;
    }
}

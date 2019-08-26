<?php

namespace Dibs\EasyCheckout\Model\System\Config\Source;

class CheckoutFlow implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray($isMultiselect=false)
    {
        $options = [];
        if (!$isMultiselect) {
            $options[] = ['value'=>'', 'label'=> ''];
        }

        $options[] = [
               'value' => 'EmbeddedCheckout',
               'label' => __('Embedded')
            ];
        $options[] = [
               'value' => 'HostedPaymentPage',
               'label' => __('Redirect')
        ];

        return $options;
    }
}

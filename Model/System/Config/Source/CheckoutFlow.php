<?php

namespace Dibs\EasyCheckout\Model\System\Config\Source;

use \Dibs\EasyCheckout\Api\CheckoutFlow as Api;

class CheckoutFlow implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @param false $isMultiselect
     *
     * @return array
     */
    public function toOptionArray($isMultiselect = false)
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
        $options[] = [
            'value' => 'OverlayPayment',
            'label' => __('Overlay')
        ];
        $options[] = [
            'value' => Api::FLOW_VANILLA,
            'label' => __('Vanilla Embeded')
        ];

        return $options;
    }
}

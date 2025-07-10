<?php

namespace Nexi\Checkout\Block\Adminhtml\Subscription\Edit;

class ResetButton extends AbstractButton
{
    public function getButtonData()
    {
        return [
            'label' => __('Reset'),
            'class' => 'reset',
            'on_click' => 'location.reload();',
            'sort_order' => 30
        ];
    }
}

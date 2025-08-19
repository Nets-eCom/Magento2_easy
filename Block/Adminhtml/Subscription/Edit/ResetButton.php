<?php
declare(strict_types=1);

namespace Nexi\Checkout\Block\Adminhtml\Subscription\Edit;

class ResetButton extends AbstractButton
{
    /**
     * Retrieves data for configuring a button.
     *
     * @return array
     */
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

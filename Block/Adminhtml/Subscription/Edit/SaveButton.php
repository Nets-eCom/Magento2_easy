<?php
declare(strict_types=1);

namespace Nexi\Checkout\Block\Adminhtml\Subscription\Edit;

class SaveButton extends AbstractButton
{
    /**
     * Retrieve configuration data for the button.
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}

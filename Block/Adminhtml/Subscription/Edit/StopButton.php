<?php

namespace Nexi\Checkout\Block\Adminhtml\Subscription\Edit;

class StopButton extends AbstractButton
{
    public function getButtonData()
    {
        $data = [];
        if ($this->getId()) {
            $data = [
                'label' => __('Stop schedule'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\''
                    . __('Cancel any unpaid orders and prevent new recurring payments from being made?')
                    . '\', \'' . $this->getStopScheduleUrl() . '\')',
                'sort_order' => 40,
            ];
        }

        return $data;
    }

    private function getStopScheduleUrl()
    {
        return $this->getUrl(
            '*/*/stopSchedule',
            [
                'id' => $this->getId()
            ]
        );
    }
}

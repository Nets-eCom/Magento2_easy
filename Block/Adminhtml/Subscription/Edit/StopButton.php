<?php
declare(strict_types=1);

namespace Nexi\Checkout\Block\Adminhtml\Subscription\Edit;

class StopButton extends AbstractButton
{
    /**
     * Get button data for the stop schedule button.
     *
     * @return array
     */
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

    /**
     * Generates and returns the URL for stopping the schedule, using the current object's identifier.
     *
     * @return string
     */
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

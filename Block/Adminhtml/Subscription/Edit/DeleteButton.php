<?php
declare(strict_types=1);

namespace Nexi\Checkout\Block\Adminhtml\Subscription\Edit;

class DeleteButton extends AbstractButton
{
    /**
     * Get button data for the delete button.
     *
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->getId()) {
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\''
                    . __('Delete this entry?')
                    . '\', \'' . $this->getDeleteUrl() . '\', {data: {}})',
                'sort_order' => 20,
            ];
        }

        return $data;
    }

    /**
     * Generates and returns the URL for the delete action, using the current object's identifier.
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['id' => $this->getId()]);
    }
}

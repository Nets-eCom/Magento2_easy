<?php

namespace Dibs\EasyCheckout\Block\Adminhtml\Widget\Grid\Column\Filter;

use Magento\Backend\Block\Widget\Grid\Column\Filter\Select as OrigSelect;

class Select extends OrigSelect
{
    public function getHtml()
    {
        $html = '<select name="' . $this->_getHtmlName() . '" id="' . $this->_getHtmlId() . '"' . $this->getUiId(
            'filter',
            $this->_getHtmlName()
        ) . 'class="no-changes admin__control-select">';
        $value = $this->getValue();
        foreach ($this->_getOptions() as $option) {
            if (!isset($option['value'])) {
                $option['value'] = '';
            }

            if (is_array($option['value'])) {
                $html .= '<optgroup label="' . $this->escapeHtml($option['label']) . '">';
                foreach ($option['value'] as $subOption) {
                    $html .= $this->_renderOption($subOption, $value);
                }
                $html .= '</optgroup>';
            } else {
                $html .= $this->_renderOption($option, $value);
            }
        }
        $html .= '</select>';
        return $html;
    }
}

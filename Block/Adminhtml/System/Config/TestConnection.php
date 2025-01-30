<?php

namespace Nexi\Checkout\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class TestConnection extends Field
{

    /**
     * Unset some non-related element parameters
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @since 100.1.0
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element = clone $element;
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Set template to itself
     *
     * @return $this
     * @since 100.1.0
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Nexi_Checkout::system/config/testconnection.phtml');
        return $this;
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label'  => __($originalData['button_label']),
                'html_id'       => $element->getHtmlId(),
                'ajax_url'      => $this->_urlBuilder->getUrl('nexi/system_config/testconnection'),
                'field_mapping' => str_replace('"', '\\"', json_encode($this->_getFieldMapping()))

            ]
        );

        return $this->_toHtml();
    }

    /**
     * Get configuration field mapping
     *
     * @return string[]
     */
    protected function _getFieldMapping(): array
    {
        return [
            'environment' => 'payment_us_nexi_environment',
            'api_key'     => 'payment_us_nexi_api_key',
        ];
    }
}

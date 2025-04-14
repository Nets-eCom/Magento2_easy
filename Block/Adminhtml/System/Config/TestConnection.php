<?php

namespace Nexi\Checkout\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class TestConnection extends Field
{
    public function __construct(
        Context                    $context,
        private readonly Structure $configStructure,
        array                      $data = [],
        ?SecureHtmlRenderer        $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
    }

    /**
     * Unset some non-related element parameters
     *
     * @param AbstractElement $element
     *
     * @return string
     * @since 100.1.0
     */
    public function render(AbstractElement $element)
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
                'field_mapping' => str_replace('"', '\\"', json_encode($this->getFieldMapping()))
            ]
        );

        return $this->_toHtml();
    }

    /**
     * Get configuration field mapping
     *
     * @return string[]
     */
    private function getFieldMapping(): array
    {
        $apiKeyPath      = $this->configStructure->getElementByConfigPath('payment/nexi/api_key');
        $testApiKeyPath  = $this->configStructure->getElementByConfigPath('payment/nexi/test_api_key');
        $environmentPath = $this->configStructure->getElementByConfigPath('payment/nexi/environment');

        return [
            'environment'  => str_replace('/', '_', $environmentPath->getPath()),
            'api_key'      => str_replace('/', '_', $apiKeyPath->getPath()),
            'test_api_key' => str_replace('/', '_', $testApiKeyPath->getPath())
        ];
    }
}

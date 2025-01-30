<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nexi\Checkout\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Nexi\Checkout\Gateway\Config\Config;

/**
 * Nexi API test connection block
 */
class TestConnection extends Field
{
    /**
     * TestConnection constructor.
     *
     * @param Context $context
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     * @param Config $gatewayConfig
     */
    public function __construct(
        Context $context,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null,
        private Config $gatewayConfig
    ) {
        parent::__construct($context, $data, $secureRenderer);
    }

    /**
     * @return $this|TestConnection
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Nexi_Checkout::system/config/testconnection.phtml');
        return $this;
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        // TODO: add Nexi URL
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('nexi-api-url'),
                'field_mapping' => str_replace('"', '\\"', json_encode($this->_getFieldMapping()))
            ]
        );

        return $this->_toHtml();
    }

    /**
     * @return string[]
     */
    protected function _getFieldMapping(): array
    {
        return $fields = [
            'api_key' => $this->gatewayConfig->getApiKey(),
            'api_identifier' => $this->gatewayConfig->getApiIdentifier(),
        ];
    }
}

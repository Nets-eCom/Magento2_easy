<?php

declare(strict_types=1);

namespace Nexi\Checkout\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Nexi\Checkout\Model\NexiSdk\HttpClientConfigurationProvider;

class CheckoutUrlInfo extends Field
{
    public function __construct(
        Context $context,
        private readonly HttpClientConfigurationProvider $clientConfigurationProvider,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
    }

    public function render(AbstractElement $element)
    {
        $nexiConfiguration = $this->clientConfigurationProvider->provide('', true);
        if ($nexiConfiguration->getBaseUrl() !== HttpClientConfigurationProvider::DEFAULT_LIVE_URL) {
            $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

            return parent::render($element);
        }

        return '';
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return '<div class="warning-enable-permissions">
            <strong>Important: U are NOT using default checkout url.</strong>
        </div>';
    }
}

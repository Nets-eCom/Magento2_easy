<?php

namespace Dibs\EasyCheckout\Block\Adminhtml\System\Config;

class Information extends \Magento\Backend\Block\Template implements
    \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    /**
     * @var string
     */
    protected
        $_template = 'dibs.phtml';

    /**
     * Render element html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $elementOriginalData = $element->getOriginalData();
        return $this->toHtml();
    }
}
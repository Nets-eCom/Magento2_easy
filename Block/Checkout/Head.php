<?php

namespace Dibs\EasyCheckout\Block\Checkout;

class Head extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    private $helper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }


    public function toHtml()
    {
        return parent::toHtml();
    }
}

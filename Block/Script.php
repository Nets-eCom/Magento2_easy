<?php
namespace Dibs\EasyCheckout\Block;


class Script extends \Magento\Framework\View\Element\Template
{

    const DIBS_JAVASCRIPT_TEST = "https://test.checkout.dibspayment.eu/v1/checkout.js?v=1";
    const DIBS_JAVASCRIPT_LIVE = "https://checkout.dibspayment.eu/v1/checkout.js?v=1";

    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    protected $helper;


    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Dibs\EasyCheckout\Helper\Data $helper
     * @param array $data
     */

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Dibs\EasyCheckout\Helper\Data $helper,
        array $data = []
    )
    {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }


    public function getSource()
    {
        if ($this->helper->isTestMode()) {
            return  self::DIBS_JAVASCRIPT_TEST;
        } else {
            return self::DIBS_JAVASCRIPT_LIVE;
        }

    }

}


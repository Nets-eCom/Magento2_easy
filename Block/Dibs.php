<?php
namespace Dibs\EasyCheckout\Block;

class Dibs extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    protected $getCurrentDibsPaymentIdService;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry,
     * @param \Dibs\EasyCheckout\Helper\Data $helper
     * @param \Dibs\EasyCheckout\Service\GetCurrentDibsPaymentId $getCurrentDibsPaymentIdService,
     * @param array $data
     */

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Dibs\EasyCheckout\Service\GetCurrentDibsPaymentId $getCurrentDibsPaymentIdService,
        array $data = []
    ) {
        $this->getCurrentDibsPaymentIdService = $getCurrentDibsPaymentIdService;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    public function getDibsCheckoutKey()
    {
        return $this->getHelper()->getApiCheckoutKey();
    }

    public function getDibsPaymentId()
    {
        return $this->getCurrentDibsPaymentIdService->getDibsPaymentId();
    }

    public function getHelper()
    {
        return $this->helper;
    }

}

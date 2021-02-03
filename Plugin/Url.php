<?php


namespace Dibs\EasyCheckout\Plugin;


use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;

class Url
{

    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    protected $helper;

    public function __construct(\Dibs\EasyCheckout\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    public function afterGetCheckoutUrl($subject,$result)
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }

        $integrationType = $this->helper->getCheckoutFlow();
        $useHostedCheckout = $integrationType === CreatePaymentCheckout::INTEGRATION_TYPE_HOSTED;

        // hosted checkout should always go through default magento checkout!
        if ($useHostedCheckout) {
            return $result;
        }

        if ($this->helper->replaceCheckout()) {
            return $this->helper->getCheckoutUrl();
        }

        return $result;

    }
}

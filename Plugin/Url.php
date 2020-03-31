<?php


namespace Dibs\EasyCheckout\Plugin;


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
        if ($this->helper->isEnabled() && $this->helper->replaceCheckout()) {
            return $this->helper->getCheckoutUrl();
        }

        return $result;
    }
}
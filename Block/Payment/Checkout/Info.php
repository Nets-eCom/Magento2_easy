<?php

namespace Dibs\EasyCheckout\Block\Payment\Checkout;


class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Dibs_EasyCheckout::payment/checkout/info.phtml';

    /**
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Dibs_EasyCheckout::payment/checkout/pdf.phtml');
        return $this->toHtml();
    }
}

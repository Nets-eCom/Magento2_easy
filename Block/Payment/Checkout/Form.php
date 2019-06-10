<?php

namespace Dibs\EasyCheckout\Block\Payment\Checkout;
/**
 * Payment method form base block
 */
class Form extends \Magento\Payment\Block\Form
{
    /**
     * @var string
     */
    protected $_template = 'Dibs_EasyCheckout::payment/checkout/form.phtml';

}

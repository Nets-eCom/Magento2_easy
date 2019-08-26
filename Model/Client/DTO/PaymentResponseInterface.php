<?php

namespace Dibs\EasyCheckout\Model\Client\DTO;

interface PaymentResponseInterface
{

    /**
     * @return string
     */
    public function getPaymentId();

    /**
     * @return string
     */
    public function getCheckoutUrl();

}

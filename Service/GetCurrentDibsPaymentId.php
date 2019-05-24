<?php


namespace Dibs\EasyCheckout\Service;


class GetCurrentDibsPaymentId extends GetCurrentQuote
{

    public function getDibsPaymentId()
    {
        return $this->checkoutSession->getDibsPaymentId();
    }
}
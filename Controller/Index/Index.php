<?php
namespace Dibs\EasyCheckout\Controller\Index;

use Dibs\EasyCheckout\Controller\Checkout;


class Index extends Checkout
{
    public function execute()
    {
        try {
            $this->dibsCheckout->initCheckout();
        } catch (\Exception $e) {

        }


    }

}
<?php
namespace Dibs\EasyCheckout\Controller\Order;

class Cart extends \Dibs\EasyCheckout\Controller\Order\Update
{
    public function execute(){
        return $this->_sendResponse(['cart','coupon','shipping','messages','dibs','grand_total'],$updateCheckout = true);
    }
}
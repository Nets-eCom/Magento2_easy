<?php

namespace Dibs\EasyCheckout\Controller\Order;


class SaveUdcShipping extends Update
{
    /**
     * We render new prices & totals
     */
    public function execute() : void
    {
        $this->_sendResponse(['grand_total', 'cart', 'dibs'], true);
    }
}

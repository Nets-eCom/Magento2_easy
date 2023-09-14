<?php

namespace Dibs\EasyCheckout\Model\Factory;

use Dibs\EasyCheckout\Model\Client\DTO\Payment\OrderItem;

class SingleOrderItemFactory {
    public static function createItem() {
        return new OrderItem();
    }
}

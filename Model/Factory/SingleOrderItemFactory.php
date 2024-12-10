<?php

namespace Dibs\EasyCheckout\Model\Factory;

use Dibs\EasyCheckout\Model\Client\DTO\Payment\OrderItem;

class SingleOrderItemFactory
{
    public function createItem(
        string $reference,
        string $name,
        string $unit,
        float $quantity,
        int $taxRate,
        int $taxAmount,
        int $unitPrice,
        int $netTotalAmount,
        int $grossTotalAmount
    ): OrderItem {
        $orderItem = new OrderItem();

        $orderItem->setReference($this->clearString($reference)); // product number
        $orderItem->setName($this->clearString($name)); // description
        $orderItem->setUnit($unit);
        $orderItem->setQuantity($quantity);
        $orderItem->setTaxRate($taxRate);
        $orderItem->setTaxAmount($taxAmount); // total tax amount
        $orderItem->setUnitPrice($unitPrice);
        $orderItem->setNetTotalAmount($netTotalAmount);
        $orderItem->setGrossTotalAmount($grossTotalAmount);

        return $orderItem;
    }

    protected function clearString(string $string): string
    {
        return preg_replace('#\+#', '\\', $string);
    }
}

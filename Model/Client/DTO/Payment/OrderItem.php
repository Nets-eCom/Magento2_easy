<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

class OrderItem extends AbstractRequest
{
    /**
     * Required
     * Product Reference (SKU?)
     */
    protected string $reference;

    /**
     * Required
     * Product Name
     */
    protected string $name;

    /**
     * Required
     * Product unit, for instance pcs or Kg
     */
    protected string $unit;

    /**
     * Required
     * Product Quantity
     */
    protected float $quantity;

    /**
     * Required
     * Product tax rate
     */
    protected int $taxRate;

    /**
     * Required
     * Product tax/VAT amount
     */
    protected int $taxAmount;

    /**
     * Required
     * Product price per unit
     */
    protected int $unitPrice;

    /**
     * Required
     * Product total amount excluding VAT
     */
    protected int $netTotalAmount;

    /**
     * Required
     * Product total amount including VAT
     */
    protected int $grossTotalAmount;

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): OrderItem
    {
        $this->reference = $reference;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): OrderItem
    {
        $this->name = $name;

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): OrderItem
    {
        $this->unit = $unit;

        return $this;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): OrderItem
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getTaxRate(): int
    {
        return $this->taxRate;
    }

    public function setTaxRate(int $taxRate): OrderItem
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    public function getTaxAmount(): int
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(int $taxAmount): OrderItem
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): OrderItem
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getNetTotalAmount(): int
    {
        return $this->netTotalAmount;
    }

    public function setNetTotalAmount(int $netTotalAmount): OrderItem
    {
        $this->netTotalAmount = $netTotalAmount;

        return $this;
    }

    public function getGrossTotalAmount(): int
    {
        return $this->grossTotalAmount;
    }

    public function setGrossTotalAmount(int $grossTotalAmount): OrderItem
    {
        $this->grossTotalAmount = $grossTotalAmount;

        return $this;
    }

    public function toArray()
    {
        return [
            'reference' => $this->getReference(),
            'name' => $this->getName(),
            'unit' => $this->getUnit(),
            'quantity' => $this->getQuantity(),
            'taxRate' => $this->getTaxRate(),
            'taxAmount' => $this->getTaxAmount(),
            'unitPrice' => $this->getUnitPrice(),
            'netTotalAmount' => $this->getNetTotalAmount(),
            'grossTotalAmount' => $this->getGrossTotalAmount(),
        ];
    }
}

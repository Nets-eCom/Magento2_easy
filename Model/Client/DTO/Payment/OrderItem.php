<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

class OrderItem extends AbstractRequest
{

    /**
     * Required
     * Product Reference (SKU?)
     * @var string $reference
     */
    protected $reference;

    /**
     * Required
     * Product Name
     * @var string $name
     */
    protected $name;

    /**
     * Required
     * Product Quantity
     * @var float $quantity
     */
    protected $quantity;

    /**
     * Required
     * Product unit, for instance pcs or Kg
     * @var string $unit
     */
    protected $unit;

    /**
     * Required
     * Product price per unit
     * @var float $unitPrice
     */
    protected $unitPrice;

    /**
     * Required
     * Product tax rate
     * @var int $taxRate
     */
    protected $taxRate;

    /**
     * Required
     * Product tax/VAT amoun
     * @var float $taxAmount
     */
    protected $taxAmount;

    /**
     * Required
     * Product total amount including VAT
     * @var
     */
    protected $grossTotalAmount;

    /**
     * Required
     * Product total amount excluding VAT
     * @var float $netTotalAmount
     */
    protected $netTotalAmount;

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     * @return OrderItem
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return OrderItem
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return OrderItem
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     * @return OrderItem
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * @return float
     */
    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    /**
     * @param float $unitPrice
     * @return OrderItem
     */
    public function setUnitPrice($unitPrice)
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    /**
     * @return int
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * @param int $taxRate
     * @return OrderItem
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;
        return $this;
    }

    /**
     * @return float
     */
    public function getTaxAmount()
    {
        return $this->taxAmount;
    }

    /**
     * @param float $taxAmount
     * @return OrderItem
     */
    public function setTaxAmount($taxAmount)
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGrossTotalAmount()
    {
        return $this->grossTotalAmount;
    }

    /**
     * @param mixed $grossTotalAmount
     * @return OrderItem
     */
    public function setGrossTotalAmount($grossTotalAmount)
    {
        $this->grossTotalAmount = $grossTotalAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getNetTotalAmount()
    {
        return $this->netTotalAmount;
    }

    /**
     * @param float $netTotalAmount
     * @return OrderItem
     */
    public function setNetTotalAmount($netTotalAmount)
    {
        $this->netTotalAmount = $netTotalAmount;
        return $this;
    }


    public function toArray()
    {

        return [
            'reference' => $this->getReference(),
            'name' => $this->getName(),
            'quantity' => $this->getQuantity(),
            'unit' => $this->getUnit(),
            'unitPrice' => $this->getUnitPrice(),
            'taxRate' => $this->getTaxRate(),
            'taxAmount' => $this->getTaxAmount(),
            'grossTotalAmount' => $this->getGrossTotalAmount(),
            'netTotalAmount' => $this->getNetTotalAmount(),
        ];
    }


}
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


    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {

        return [
            'reference' => $this->reference,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unitPrice' => $this->unitPrice,
            'taxRate' => $this->taxRate,
            'taxAmount' => $this->taxAmount,
            'grossTotalAmount' => $this->grossTotalAmount,
            'netTotalAmount' => $this->netTotalAmount,
        ];
    }


}
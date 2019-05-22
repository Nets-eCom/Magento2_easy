<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

class PaymentOrder extends AbstractRequest
{

    /**
     * Required
     * @var float $amount
     */
    protected $amount;

    /**
     * Required
     * @var string $currency
     */
    protected $currency;

    /**
     * Required
     * Magento Order ID? Or Quote ID?
     * @var string $reference
     */
    protected $reference;

    /**
     * Required
     * @var $items OrderItem[]
     */
    protected $items;

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return PaymentOrder
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return PaymentOrder
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     * @return PaymentOrder
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
        return $this;
    }

    /**
     * @return OrderItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param OrderItem[] $items
     * @return PaymentOrder
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }


    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        $items = [];
        if (!empty($this->getItems())) {
            foreach ($this->getItems() as $item) {
                $items[] = $item->toArray();
            }
        }

        return [
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'reference' => $this->getReference(),
            'items' => $items
        ];
    }


}
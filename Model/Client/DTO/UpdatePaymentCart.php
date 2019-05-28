<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;


use Dibs\EasyCheckout\Model\Client\DTO\Payment\OrderItem;

class UpdatePaymentCart extends AbstractRequest
{

    /**
     * Required
     * @var float $amount
     */
    protected $amount;

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
     * @return UpdatePaymentCart
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
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
     * @return UpdatePaymentCart
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
            'items' => $items
        ];
    }


}
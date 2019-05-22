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


    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        $items = [];
        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                $items[] = $item->toArray();
            }
        }

        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'reference' => $this->reference,
            'items' => $items
        ];
    }


}
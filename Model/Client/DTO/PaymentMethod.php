<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;


use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentWebhook;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentOrder;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\OrderItem;

class PaymentMethod extends AbstractRequest
{

    /** @var string $name */
    protected $name;

    /** @var OrderItem */
    protected $fee;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return PaymentMethod
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return OrderItem
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param OrderItem $fee
     * @return PaymentMethod
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
        return $this;
    }



    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
            'fee' => $this->fee->toArray(),
        ];
    }


}
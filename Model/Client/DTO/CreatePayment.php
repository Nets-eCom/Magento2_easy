<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;


use Dibs\EasyCheckout\Model\Client\DTO\Payment\PaymentOrder;

class CreatePayment extends AbstractRequest
{

    /** @var PaymentOrder */
    protected $order;

    /** @var CreatePaymentCheckout */
    protected $checkout;

    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        return [
            'order' => $this->order->toArray(),
            'checkout' => $this->checkout->toArray(),
        ];
    }


}
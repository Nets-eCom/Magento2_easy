<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;


use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentWebhook;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\PaymentOrder;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;

class CreatePayment extends AbstractRequest
{

    /** @var PaymentOrder */
    protected $order;

    /** @var CreatePaymentCheckout */
    protected $checkout;

    /** @var CreatePaymentWebhook[] */
    protected $webHooks;


    /**
     * @return PaymentOrder
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param PaymentOrder $order
     * @return CreatePayment
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return CreatePaymentCheckout
     */
    public function getCheckout()
    {
        return $this->checkout;
    }

    /**
     * @param CreatePaymentCheckout $checkout
     * @return CreatePayment
     */
    public function setCheckout($checkout)
    {
        $this->checkout = $checkout;
        return $this;
    }


    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        $data = [
            'order' => $this->order->toArray(),
            'checkout' => $this->checkout->toArray(),
        ];

        if ($this->webHooks) {
            $data['notifications']['webHooks'] = $this->webHooks;
        }

        return $data;
    }


}
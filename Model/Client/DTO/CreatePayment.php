<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;

use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentOrder;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentWebhook;

class CreatePayment extends AbstractRequest
{

    /** @var CreatePaymentOrder */
    protected $order;

    /** @var CreatePaymentCheckout */
    protected $checkout;

    /** @var CreatePaymentWebhook[] */
    protected $webHooks;

    /**
     * @var PaymentMethod[] $paymentMethods
     */
    protected $paymentMethods;

    /**
     * @return CreatePaymentOrder
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param CreatePaymentOrder $order
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

    /**
     * @return CreatePaymentWebhook[]
     */
    public function getWebHooks()
    {
        return $this->webHooks;
    }

    /**
     * @param CreatePaymentWebhook[] $webHooks
     * @return CreatePayment
     */
    public function setWebHooks($webHooks)
    {
        $this->webHooks = $webHooks;
        return $this;
    }

    /**
     * @return PaymentMethod[]
     */
    public function getPaymentMethods()
    {
        return $this->paymentMethods;
    }

    /**
     * @param PaymentMethod[] $paymentMethods
     * @return CreatePayment
     */
    public function setPaymentMethods($paymentMethods)
    {
        $this->paymentMethods = $paymentMethods;
        return $this;
    }

    public function toJSON()
    {
        return json_encode(
            $this->utf8ize($this->toArray())
        );
    }

    public function toArray()
    {
        $data = [
            'order' => $this->order->toArray(),
            'checkout' => $this->checkout->toArray(),
        ];

        if ($this->webHooks) {
            $webhooks = [];
            foreach ($this->webHooks as $w) {
                $webhooks[] = $w->toArray();
            }
            $data['notifications']['webhooks'] = $webhooks;
        }

        if ($this->paymentMethods) {
            $methods = [];
            foreach ($this->paymentMethods as $p) {
                $methods[] = $p->toArray();
            }
            $data['paymentMethods'] = $methods;
        }

        return $data;
    }
}

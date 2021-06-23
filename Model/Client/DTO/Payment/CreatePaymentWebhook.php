<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

class CreatePaymentWebhook extends AbstractRequest
{

    const EVENT_PAYMENT_CREATED = 'payment.created';
    const EVENT_PAYMENT_RESERVATION_CREATED = 'payment.reservation.created';
    const EVENT_PAYMENT_CHECKOUT_COMPLETED = 'payment.checkout.completed';
    const EVENT_PAYMENT_CHARGE_CREATED = 'payment.charge.created';
    const EVENT_PAYMENT_CHARGE_FAILED = 'payment.charge.failed';
    const EVENT_PAYMENT_REFUND_INITIATED = 'payment.refund.initiated';
    const EVENT_PAYMENT_REFUND_FAILED = 'payment.refund.failed';
    const EVENT_PAYMENT_REFUND_COMPLETED= 'payment.refund.completed';
    const EVENT_PAYMENT_CANCEL_CREATED = 'payment.cancel.created';
    const EVENT_PAYMENT_CANCEL_FAILED = 'payment.cancel.failed';

    /**
     * Valid events with corresponding Webhook Controller name (if it exists)
     */
    const VALID_EVENTS = [
        self::EVENT_PAYMENT_CREATED => '',
        self::EVENT_PAYMENT_RESERVATION_CREATED => 'ReservationCreated',
        self::EVENT_PAYMENT_CHECKOUT_COMPLETED => 'CheckoutCompleted',
        self::EVENT_PAYMENT_CHARGE_CREATED => '',
        self::EVENT_PAYMENT_CHARGE_FAILED => '',
        self::EVENT_PAYMENT_REFUND_INITIATED => '',
        self::EVENT_PAYMENT_REFUND_FAILED => '',
        self::EVENT_PAYMENT_REFUND_COMPLETED => '',
        self::EVENT_PAYMENT_CANCEL_CREATED => '',
        self::EVENT_PAYMENT_CANCEL_FAILED => '',
    ];

    /** @var $eventName string */
    protected $eventName;

    /** @var $url string */
    protected $url;

    /** @var $url string */
    protected $authorization;

    /**
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * @param string $eventName
     * @return CreatePaymentWebhook
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return CreatePaymentWebhook
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorization()
    {
        return $this->authorization;
    }

    /**
     * @param string $authorization
     * @return CreatePaymentWebhook
     */
    public function setAuthorization($authorization)
    {
        $this->authorization = $authorization;
        return $this;
    }

    /**
     * Get controller name for set event
     *
     * @return string
     */
    public function getControllerName()
    {
        return self::VALID_EVENTS[$this->eventName] ?? '';
    }

    public function toArray()
    {
        if (!in_array($this->getEventName(), array_keys(self::VALID_EVENTS))) {
            throw new \Exception("The event '" . $this->getEventName() . "' is not a valid event name");
        }

        return [
            'url' => $this->getUrl(),
            'eventName' => $this->getEventName(),
            'authorization' => $this->getAuthorization(),
        ];
    }
}

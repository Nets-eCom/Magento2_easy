<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

class ConsumerPhoneNumber extends AbstractRequest
{

    /**
     * @var string $prefix
     */
    protected $prefix;

    /**
     * @var string $number
     */
    protected $number;

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     * @return ConsumerPhoneNumber
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $number
     * @return ConsumerPhoneNumber
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }


    public function getPhoneNumber()
    {
        return $this->getPrefix() . $this->getNumber();
    }

    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        return [
            'prefix' => $this->getPrefix(),
            'number' => $this->getNumber(),
        ];
    }


}
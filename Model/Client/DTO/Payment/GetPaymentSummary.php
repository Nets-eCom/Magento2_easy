<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

class GetPaymentSummary
{

    /**
     * Required if $chargedAmount is null
     * @var float $reservedAmount
     */
    protected $reveredAmount;

    /**
     * Required if $reservedAmount is null
     * @var float $chargedAmount
     */
    protected $chargedAmount;

    /**
     * @return float
     */
    public function getReservedAmount()
    {
        return $this->reveredAmount;
    }

    public function getChargedAmount()
    {
        return $this->chargedAmount;
    }

    /**
     * @param float $amount
     * @return GetPaymentSummary
     */
    public function setReservedAmount($amount)
    {
        $this->reveredAmount = $amount;
        return $this;
    }

    /**
     * @param float $amount
     * @return GetPaymentSummary
     */
    public function setChargedAmount($amount)
    {
        $this->chargedAmount = $amount;
        return $this;
    }
}

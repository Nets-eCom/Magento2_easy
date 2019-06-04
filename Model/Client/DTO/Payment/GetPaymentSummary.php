<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

class GetPaymentSummary
{

    /**
     * Required
     * @var float $reservedAmount
     */
    protected $reveredAmount;

    /**
     * @return float
     */
    public function getReservedAmount()
    {
        return $this->reveredAmount;
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


}
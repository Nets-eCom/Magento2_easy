<?php

namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

class GetChargeDetails {

    /**
     * Required if $chargeId is null
     * @var string $chargeId */
    protected $chargeId;

    /**
     * Required if $amount is null
     * @var float $amount */
    protected $amount;

    /**
     * @return string
     */
    public function getChargeId() {
        return $this->chargeId;
    }

    /**
     * @param string $chargeId
     * @return GetChargeDetails
     */
    public function setChargeId($chargeId) {
        $this->chargeId = $chargeId;
        return $this;
    }

    /**
     * @return string
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * @param string $amount
     * @return GetChargeDetails
     */
    public function setAmount($amount) {
        $this->amount = $amount;
        return $this;
    }

}

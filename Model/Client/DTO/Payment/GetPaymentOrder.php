<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

class GetPaymentOrder
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
     * Magento Order ID? Or Quote ID
     * @var string $reference
     */
    protected $reference;

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return GetPaymentOrder
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return GetPaymentOrder
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     * @return GetPaymentOrder
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
        return $this;
    }
}
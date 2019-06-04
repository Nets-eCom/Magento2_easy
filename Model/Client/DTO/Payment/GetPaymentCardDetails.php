<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

class GetPaymentCardDetails
{

    /** @var string $maskedPan */
   protected $maskedPan;

   /** @var string $expiryDate */
   protected $expiryDate;

    /**
     * @return string
     */
    public function getMaskedPan()
    {
        return $this->maskedPan;
    }

    /**
     * @param string $maskedPan
     * @return GetPaymentCardDetails
     */
    public function setMaskedPan($maskedPan)
    {
        $this->maskedPan = $maskedPan;
        return $this;
    }

    /**
     * @return string
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * @param string $expiryDate
     * @return GetPaymentCardDetails
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;
        return $this;
    }



}
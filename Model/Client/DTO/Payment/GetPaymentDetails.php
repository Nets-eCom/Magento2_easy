<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

class GetPaymentDetails
{

    /** @var string $paymentType */
   protected $paymentType;

   /** @var string $paymentMethod */
   protected $paymentMethod;

   /** @var GetPaymentInvoiceDetails $invoiceDetails */
   protected $invoiceDetails;

   /** @var GetPaymentCardDetails $cardDetails */
   protected $cardDetails;

    /**
     * @return string
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * @param string $paymentType
     * @return GetPaymentDetails
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     * @return GetPaymentDetails
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * @return GetPaymentInvoiceDetails
     */
    public function getInvoiceDetails()
    {
        return $this->invoiceDetails;
    }

    /**
     * @param GetPaymentInvoiceDetails $invoiceDetails
     * @return GetPaymentDetails
     */
    public function setInvoiceDetails($invoiceDetails)
    {
        $this->invoiceDetails = $invoiceDetails;
        return $this;
    }

    /**
     * @return GetPaymentCardDetails
     */
    public function getCardDetails()
    {
        return $this->cardDetails;
    }

    /**
     * @param GetPaymentCardDetails $cardDetails
     * @return GetPaymentDetails
     */
    public function setCardDetails($cardDetails)
    {
        $this->cardDetails = $cardDetails;
        return $this;
    }

}
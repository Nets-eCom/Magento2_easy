<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;


class CreatePaymentChargeResponse
{


    /** @var string $chargeId */
    protected $chargeId;

    /** @var string $invoiceNumber */
    protected $invoiceNumber;

    /**
     * CreatePaymentChargeResponse constructor.
     * @param $response string
     */
    public function __construct($response = "")
    {
        if ($response !== "") {
            $data = json_decode($response, true);
            $this->setChargeId($data['chargeId']);

            if (isset($data['invoice']['invoiceNumber'])) {
                $this->setInvoiceNumber($data['invoice']['invoiceNumber']);
            }
        }
    }

    /**
     * @return string
     */
    public function getChargeId()
    {
        return $this->chargeId;
    }

    /**
     * @param string $chargeId
     * @return CreatePaymentChargeResponse
     */
    public function setChargeId($chargeId)
    {
        $this->chargeId = $chargeId;
        return $this;
    }

    /**
     * @return string
     */
    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    /**
     * @param string $invoiceNumber
     * @return CreatePaymentChargeResponse
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;
        return $this;
    }



}
<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;


class CreatePaymentResponse
{


    /** @var string $paymentId */
    protected $paymentId;

    /**
     * CreatePaymentResponse constructor.
     * @param $response string
     */
    public function __construct($response = "")
    {
        if ($response !== "") {
            $data = json_decode($response);
            $this->setPaymentId($data->paymentId);
        }
    }

    /**
     * @return string
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }


    /**
     * @param string $paymentId
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    }

}
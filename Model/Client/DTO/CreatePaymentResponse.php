<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;

class CreatePaymentResponse implements PaymentResponseInterface
{

    /** @var string $paymentId */
    protected $paymentId;

    /** @var string $checkoutUrl */
    protected $checkoutUrl;

    /**
     * CreatePaymentResponse constructor.
     * @param $response string
     */
    public function __construct($response = "")
    {
        if ($response !== "") {
            $data = json_decode($response, true);
            $this->setPaymentId($data['paymentId']);

            if (isset($data['hostedPaymentPageUrl'])) {
                $this->setCheckoutUrl($data['hostedPaymentPageUrl']);
            }
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


    /**
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->checkoutUrl;
    }

    /**
     * @param string $checkoutUrl
     */
    public function setCheckoutUrl($checkoutUrl)
    {
        $this->checkoutUrl = $checkoutUrl;
    }


}

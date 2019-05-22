<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

class CreatePaymentCheckout extends AbstractRequest
{

    /**
     * Required
     * The URL of where the checkout should initialize on (mandatory)
     * @var string $url
     */
    protected $url;

    /**
     * Required
     * The URL to your terms and conditions
     * @var string $termsUrl
     */
    protected $termsUrl;

    /**
     * Optional
     * Configures which consumers types should be accepted (will default to B2C if not entered)
     * @var $consumerType ConsumerType
     */
    protected $consumerType;

    /**
     * Optional
     * Default value = false, if set to true the checkout will not load any user data.
     * @var bool $publicDevice
     */
    protected $publicDevice;

    /**
     * Optional
     *  Default value = false, if set to true the transaction will be charged automatically after reservation have been accepted without calling the Charge API.
     * @var bool $charge
     */
    protected $charge;

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return CreatePaymentCheckout
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getTermsUrl()
    {
        return $this->termsUrl;
    }

    /**
     * @param string $termsUrl
     * @return CreatePaymentCheckout
     */
    public function setTermsUrl($termsUrl)
    {
        $this->termsUrl = $termsUrl;
        return $this;
    }

    /**
     * @return ConsumerType
     */
    public function getConsumerType()
    {
        return $this->consumerType;
    }

    /**
     * @param ConsumerType $consumerType
     * @return CreatePaymentCheckout
     */
    public function setConsumerType($consumerType)
    {
        $this->consumerType = $consumerType;
        return $this;
    }

    /**
     * @return bool
     */
    public function getPublicDevice()
    {
        return (bool) $this->publicDevice;
    }

    /**
     * @param bool $publicDevice
     * @return CreatePaymentCheckout
     */
    public function setPublicDevice($publicDevice)
    {
        $this->publicDevice = $publicDevice;
        return $this;
    }

    /**
     * @return bool
     */
    public function getCharge()
    {
        return $this->charge;
    }

    /**
     * @param bool $charge
     * @return CreatePaymentCheckout
     */
    public function setCharge($charge)
    {
        $this->charge = (bool) $charge;
        return $this;
    }



    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        $data = [
            'url' => $this->getUrl(),
            'termsUrl' => $this->getTermsUrl(),
        ];

        // optional
        if ($this->consumerType instanceof ConsumerType) {
            $data['consumerType'] = $this->getConsumerType()->toArray();
        }

        if ($this->publicDevice !== null) {
            $data['publicDevice'] = $this->getPublicDevice();
        }

        if ($this->charge !== null) {
            $data['charge'] = $this->getCharge();
        }

        return $data;
    }


}
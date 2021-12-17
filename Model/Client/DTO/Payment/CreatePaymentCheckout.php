<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

class CreatePaymentCheckout extends AbstractRequest
{

    const INTEGRATION_TYPE_EMBEDDED = "EmbeddedCheckout";
    const INTEGRATION_TYPE_HOSTED = "HostedPaymentPage";
    const INTEGRATION_TYPE_OVERLAY = "OverlayPayment";

    /**
     * Required|Optional (if $integrationType = EmbeddedCheckout, default!)
     * The URL of where the checkout should initialize on (mandatory)
     * @var string $url
     */
    protected $url;

    /**
     * Optional|Required (if integrationType = HostedPaymentPage)
     * Specify where customer will return
     * @var string $url
     */
    protected $returnUrl;

    /**
     * Required
     * The URL to your terms and conditions
     * @var string $termsUrl
     */
    protected $termsUrl;

    /**
     * @var mixed
     */
    protected $cancelUrl;

    /**
     * Optional
     * if merchantHandlesConsumerData = false specify which consumerTypes should be available in checkout. (B2B or B2C),
     * if merchantHandlesConsumerData=true these parameters will be ignored.
     *
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
     * Default value = false, if set to true the transaction will be charged automatically after reservation have been accepted without calling the Charge API.
     * Flags immediate full charge on a reserved authorization
     * @var bool $charge
     */
    protected $charge;

    /**
     * HostedPaymentPage, EmbeddedCheckout OR OverlayPayment, default value = EmbeddedCheckout
     * @var $integrationType string|null
     */
    protected $integrationType;

    /**
     * Optional
     * Enables the merchant to pre-fill the checkout with customer data.
     * If set to true, requires consumer parameters (either privateperson or company, not both)
     * @var bool $merchantHandlesConsumerData;
     */
    protected $merchantHandlesConsumerData;

    /**
     * Optional
     * Enables the merchant to handle the shipping cost
     * If set to true, requires paymentID to be updated with shipping.costSpecified = true before customer can complete a purchase.
     * @var bool $merchantHandlesShippingCost;
     */
    protected $merchantHandlesShippingCost;


    /**
     * will be converted to shipping -> countries[].countryCode
     * @var array
     */
    protected $shippingCountries;

    /** @var Consumer $consumer */
    protected $consumer;

    /**
     * @var bool
     */
    private $enableBillingAddress;

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
     * @return string
     */
    public function getPrivacyUrl()
    {
        return $this->privacyUrl;
    }

    /**
     * @param string $privacyUrl
     * @return CreatePaymentCheckout
     */
    public function setPrivacyUrl($privacyUrl)
    {
        $this->privacyUrl = $privacyUrl;
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

    /**
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * @param string $returnUrl
     * @return CreatePaymentCheckout
     */
    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getIntegrationType()
    {
        return $this->integrationType;
    }

    /**
     * @param string|null $integrationType
     * @return CreatePaymentCheckout
     */
    public function setIntegrationType($integrationType)
    {
        $this->integrationType = $integrationType;
        return $this;
    }

    /**
     * @return bool
     */
    public function getMerchantHandlesConsumerData()
    {
        return (bool) $this->merchantHandlesConsumerData;
    }

    /**
     * @param bool $merchantHandlesConsumerData
     * @return CreatePaymentCheckout
     */
    public function setMerchantHandlesConsumerData($merchantHandlesConsumerData)
    {
        $this->merchantHandlesConsumerData = $merchantHandlesConsumerData;
        return $this;
    }

    public function enableBillingAddress()
    {
        $this->enableBillingAddress = true;
    }

    /**
     * @return bool
     */
    public function getMerchantHandlesShippingCost()
    {
        return (bool) $this->merchantHandlesShippingCost;
    }

    /**
     * @param bool $merchantHandlesShippingCost
     * @return CreatePaymentCheckout
     */
    public function setMerchantHandlesShippingCost($merchantHandlesShippingCost)
    {
        $this->merchantHandlesShippingCost = $merchantHandlesShippingCost;
        return $this;
    }

    /**
     * @return Consumer
     */
    public function getConsumer()
    {
        return $this->consumer;
    }

    /**
     * @param Consumer $consumer
     * @return CreatePaymentCheckout
     */
    public function setConsumer($consumer)
    {
        $this->consumer = $consumer;
        return $this;
    }

    /**
     * @return array
     */
    public function getShippingCountries()
    {
        return $this->shippingCountries;
    }

    /**
     * @param array $shippingCountries
     * @return CreatePaymentCheckout
     */
    public function setShippingCountries($shippingCountries)
    {
        $this->shippingCountries = $shippingCountries;
        return $this;
    }



    public function toArray()
    {
        $data = [
            'termsUrl' => $this->getTermsUrl(),
            'merchantTermsUrl' => $this->getPrivacyUrl(),
            'consumer' => null,
        ];

        /*$data = [
            'merchantTermsUrl' => $this->getTermsUrl(),
            'consumer' => null,
        ];*/

        if (!empty($this->getShippingCountries()) && is_array($this->getShippingCountries())) {

            // set the structure
            $countries = [];
            foreach ($this->getShippingCountries() as $countryIso) {
                $countries[] = ['countryCode' => $countryIso];
            }

            $data['ShippingCountries'] = $countries;
        }

        if ($this->getConsumer() instanceof Consumer) {
            $data['consumer'] = $this->getConsumer()->toArray();
        }

        if ($this->merchantHandlesConsumerData !== null) {
            $data['merchantHandlesConsumerData'] = $this->getMerchantHandlesConsumerData();
        }

        if ($this->merchantHandlesShippingCost !== null) {
            $data['merchantHandlesShippingCost'] = $this->getMerchantHandlesShippingCost();
        }

        if ($this->enableBillingAddress) {
            $data['shipping']['enableBillingAddress'] = $this->enableBillingAddress;
        }

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

        if ($this->integrationType !== null) {
            $data['integrationType'] = $this->getIntegrationType();
            $data['returnUrl'] = $this->getReturnUrl();
        }

        if ($this->cancelUrl) {
            $data['cancelUrl'] = $this->getCancelUrl();
        }

        // url is required when we use EmbeddedCheckout as integration type. Which is default!
        if ($this->integrationType === null || $this->integrationType === "EmbeddedCheckout") {
            $data['url'] = $this->getUrl();
        }

        return $data;
    }

    /**
     * @return mixed
     */
    public function getCancelUrl()
    {
        return $this->cancelUrl;
    }

    /**
     * @param mixed $cancelUrl
     */
    public function setCancelUrl($cancelUrl): void
    {
        $this->cancelUrl = $cancelUrl;
    }

}
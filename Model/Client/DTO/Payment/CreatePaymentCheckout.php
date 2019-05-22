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
     * I guess this is used when you have full iframe?
     * Optional
     * Limits the shipping countries
     //@var ShippingCountry[] $shippingCountries
     */
   // protected $shippingCountries;

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

    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        $data = [
            'url' => $this->url,
            'termsUrl' => $this->termsUrl,
        ];

        // optional
        if ($this->consumerType instanceof ConsumerType) {
            $data['consumerType'] = $this->consumerType->toArray();
        }

        if ($this->publicDevice !== null) {
            $data['publicDevice'] = (bool) $this->publicDevice;
        }

        if ($this->charge !== null) {
            $data['charge'] = (bool) $this->charge;
        }

        return $data;
    }


}
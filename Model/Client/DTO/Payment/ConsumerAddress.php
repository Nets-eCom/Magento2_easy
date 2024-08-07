<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

class ConsumerAddress extends AbstractRequest
{

    /**
     * @var string addressLine1
     */
    protected $addressLine1;

    /**
     * @var string addressLine2
     */
    protected $addressLine2;

    /**
     * @var string $postalCode
     */
    protected $postalCode;

    /**
     * @var string $city
     */
    protected $city;

    /**
     * @var string $country
     */
    protected $country;

    /**
     * @return string
     */
    public function getAddressLine1()
    {
        return $this->addressLine1;
    }

    /**
     * @param string $addressLine1
     * @return ConsumerAddress
     */
    public function setAddressLine1($addressLine1)
    {
        $this->addressLine1 = $addressLine1;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddressLine2()
    {
        return $this->addressLine2;
    }

    /**
     * @param string $addressLine2
     * @return ConsumerAddress
     */
    public function setAddressLine2($addressLine2)
    {
        $this->addressLine2 = $addressLine2;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     * @return ConsumerAddress
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return ConsumerAddress
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return ConsumerAddress
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }


    public function toArray()
    {

        return [
            'addressLine1' => $this->getAddressLine1(),
            'addressLine2' => $this->getAddressLine2(),
            'postalCode' => $this->formatPostalCode(),
            'city' => $this->getCity(),
            'country' => $this->getCountry(),
        ];
    }

    /**
     * Makes postal code compatible with the API's required format
     *
     * @return string
     */
    private function formatPostalCode()
    {
        return str_replace(' ', '', $this->postalCode);
    }
}

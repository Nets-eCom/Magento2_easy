<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

class GetConsumerCompany
{

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var string $registrationNumber
     */
    protected $registrationNumber;

    /** @var GetConsumerCompanyContactDetails $contactDetails */
    protected $contactDetails;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return GetConsumerCompany
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getRegistrationNumber()
    {
        return $this->registrationNumber;
    }

    /**
     * @param string $registrationNumber
     * @return GetConsumerCompany
     */
    public function setRegistrationNumber($registrationNumber)
    {
        $this->registrationNumber = $registrationNumber;
        return $this;
    }

    /**
     * @return GetConsumerCompanyContactDetails
     */
    public function getContactDetails()
    {
        return $this->contactDetails;
    }

    /**
     * @param GetConsumerCompanyContactDetails $contactDetails
     * @return GetConsumerCompany
     */
    public function setContactDetails($contactDetails)
    {
        $this->contactDetails = $contactDetails;
        return $this;
    }




}
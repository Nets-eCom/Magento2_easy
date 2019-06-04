<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

class GetConsumerPrivatePerson
{

    /**
     * @var string $firstName
     */
    protected $firstName;

    /**
     * @var string $lastName
     */
    protected $lastName;

    /** @var string $email */
    protected $email;

    /** @var ConsumerPhoneNumber $phoneNumber */
    protected $phoneNumber;

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return GetConsumerPrivatePerson
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }



    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return GetConsumerPrivatePerson
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     * @return GetConsumerPrivatePerson
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return ConsumerPhoneNumber
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param ConsumerPhoneNumber $phoneNumber
     * @return GetConsumerPrivatePerson
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }


}
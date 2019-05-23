<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

class ConsumerContact extends AbstractRequest
{

    /**
     * @var string $firstName
     */
    protected $firstName;

    /**
     * @var string $lastName
     */
    protected $lastName;

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return ConsumerContact
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
     * @return ConsumerContact
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }


    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        return [
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
        ];
    }


}
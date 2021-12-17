<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

class ConsumerCompany extends AbstractRequest
{

    /**
     * Company Name
     * @var string $name
     */
    protected $name;

    /** @var ConsumerContact $contact */
    protected $contact;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ConsumerCompany
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return ConsumerContact
     */
    public function getContact()
    {
        //return $this->contact;
        return [
            "firstname" => $this->firstname,
            "lastname" => $this->lastname
        ];
    }

    /**
     * @param ConsumerContact $contact
     * @return ConsumerCompany
     */
    public function setContact($firstname, $lastname)
    {

        $this->firstname = $firstname;
        $this->lastname = $lastname;
        return $this;
    }


    public function toArray()
    {
        return [
            "name" => $this->getName(),
            "contact" => $this->getContact()
        ];
    }
}
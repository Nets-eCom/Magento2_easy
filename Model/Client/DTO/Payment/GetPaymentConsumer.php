<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

class GetPaymentConsumer
{
    /**
     * Consumer = Customer
     */

    /** @var GetConsumerShippingAddress $shippingAddress */
    protected $shippingAddress;


    /**
     * @var GetConsumerPrivatePerson $privatePerson
     */
    protected $privatePerson;

    /**
     * @var GetConsumerCompany $company
     */
    protected $company;


    /**
     * @return GetConsumerShippingAddress
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * @param GetConsumerShippingAddress $shippingAddress
     * @return GetPaymentConsumer
     */
    public function setShippingAddress($shippingAddress)
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    /**
     * @return GetConsumerPrivatePerson
     */
    public function getPrivatePerson()
    {
        return $this->privatePerson;
    }

    /**
     * @param GetConsumerPrivatePerson $privatePerson
     * @return GetPaymentConsumer
     */
    public function setPrivatePerson($privatePerson)
    {
        $this->privatePerson = $privatePerson;
        return $this;
    }

    /**
     * @return GetConsumerCompany
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param GetConsumerCompany $company
     * @return GetPaymentConsumer
     */
    public function setCompany($company)
    {
        $this->company = $company;
        return $this;
    }


}
<?php

namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

class Consumer extends AbstractRequest {
    /**
     * Consumer = Customer
     */

    /**
     * Consumer Reference, customer ID
     * @var string $reference
     */
    protected $reference;

    /**
     * Consumer Email
     * @var string $email
     */
    protected $email;

    /** @var ConsumerAddress $shippingAddress */
    protected $shippingAddress;

    /** @var ConsumerAddress $billingAddress */
    protected $billingAddress;

    /** @var ConsumerPhoneNumber $phoneNumber */
    protected $phoneNumber;

    /**
     * NB! Either pass "privatePerson" or "company", NOT BOTH!
     * fill out if the consumer is a private person, if not, it must be omitted from the payload
     * @var ConsumerPrivatePerson $privatePerson
     */
    protected $privatePerson;

    /**
     * NB! Either pass "privatePerson" or "company", NOT BOTH!
     * fill out if the consumer is a company, if not, it must be omitted from the payload
     * @var ConsumerCompany $company
     */
    protected $company;

    /**
     * @return string
     */
    public function getReference() {
        return $this->reference;
    }

    /**
     * @param string $reference
     * @return Consumer
     */
    public function setReference($reference) {
        $this->reference = $reference;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @param string $email
     * @return Consumer
     */
    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    /**
     * @return ConsumerAddress
     */
    public function getShippingAddress() {
        return $this->shippingAddress;
    }

    /**
     * @param ConsumerAddress $shippingAddress
     * @return Consumer
     */
    public function setShippingAddress($shippingAddress) {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    /**
     * @return ConsumerAddress|null
     */
    public function getBillingAddress() {
        return $this->billingAddress;
    }

    /**
     * @param ConsumerAddress $billingAddress
     * @return Consumer
     */
    public function setBillingAddress($billingAddress) {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    /**
     * @return ConsumerPhoneNumber
     */
    public function getPhoneNumber() {
        return $this->phoneNumber;
    }

    /**
     * @param ConsumerPhoneNumber $phoneNumber
     * @return Consumer
     */
    public function setPhoneNumber($phoneNumber) {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * @return ConsumerPrivatePerson
     */
    public function getPrivatePerson() {
        return $this->privatePerson;
    }

    /**
     * @param ConsumerPrivatePerson $privatePerson
     * @return Consumer
     */
    public function setPrivatePerson($privatePerson) {
        $this->privatePerson = $privatePerson;
        return $this;
    }

    /**
     * @return ConsumerCompany
     */
    public function getCompany() {
        return $this->company;
    }

    /**
     * @param ConsumerCompany $company
     * @return Consumer
     */
    public function setCompany($company) {
        $this->company = $company;
        return $this;
    }

    public function toArray() {
        $data = [
            "reference" => $this->getReference(),
            "email" => $this->getEmail(),
            "shippingAddress" => $this->getShippingAddress()->toArray(),
        ];

        // billing address filled only if different then shipping
        if ($this->getBillingAddress()) {
            $data["billingAddress"] = $this->getBillingAddress()->toArray();
        }

        //If phone number is not empty
        if ($this->getPhoneNumber()) {
            $data["phoneNumber"] = $this->getPhoneNumber()->toArray();
        }
        // fill out if the consumer is a company, if not, it must be omitted from the payload
        if ($company = $this->getCompany()) {
            $data['company'] = $company->toArray();
        }

        // fill out if the consumer is a private person, if not, it must be omitted from the payload
        if ($person = $this->getPrivatePerson()) {
            $data['privatePerson'] = $person->toArray();
        }

        //NB! Either pass "privatePerson" or "company", NOT BOTH!
        if (isset($data['company']) && isset($data['privatePerson'])) {
            throw new \Exception("Payment Consumer: Either pass \"privatePerson\" or \"company\", NOT BOTH!");
        }

        return $data;
    }

}

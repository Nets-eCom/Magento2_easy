<?php

declare(strict_types=1);

namespace Dibs\EasyCheckout\Model\Quote;

use Dibs\EasyCheckout\Model\Client\DTO\Payment\Consumer;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerPhoneNumber;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerPrivatePerson;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerCompany;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerAddress;
use Magento\Quote\Model\Quote;
use Dibs\EasyCheckout\Model\Dibs\LocaleFactory;
use Dibs\EasyCheckout\Helper\Data;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class ConsumerDataProvider
{
    private LocaleFactory $localeFactory;

    private Data $helper;

    public function __construct(
        LocaleFactory $localeFactory,
        Data $helper

    ) {
        $this->localeFactory = $localeFactory;
        $this->helper = $helper;
    }

    /**
     * @param Quote $quote
     *
     * @return Consumer
     * @throws \Exception
     */
    public function getFromQuote(Quote $quote): Consumer
    {
        if (!$quote->isVirtual()) {
            $consumer = new Consumer();
            $consumer->setReference($quote->getCustomerId());

            $consumer->setShippingAddress($this->getAddressData($quote));
            //If phone number is not empty
            if ($quote->getShippingAddress()->getTelephone()) {
                $consumer->setPhoneNumber($this->getPhoneNumber($quote));
            }
            $consumer->setEmail($quote->getBillingAddress()->getEmail());
            $weHandleConsumer = $this->helper->doesHandleCustomerData();
            if ($weHandleConsumer && !empty($quote->getShippingAddress()->getCompany())) {
                $consumer->setCompany($this->getCompanyData($quote));
            } else {
                $consumer->setPrivatePerson($this->getPrivatePersonData($quote));
            }

            if ($this->helper->getSplitAddresses() && !$quote->getShippingAddress()->getSameAsBilling()) {
                $consumer->setBillingAddress($this->getBillingAddressData($quote));
            }
        } else {
            $consumer = new Consumer();
            $consumer->setReference($quote->getCustomerId());

            $consumer->setShippingAddress($this->getBillingAddressData($quote));
            //If phone number is not empty
            if ($quote->getBillingAddress()->getTelephone()) {
                $consumer->setPhoneNumber($this->getPhoneNumber($quote));
            }
            $consumer->setEmail($quote->getBillingAddress()->getEmail());
            $weHandleConsumer = $this->helper->doesHandleCustomerData();
            if ($weHandleConsumer && !empty($quote->getBillingAddress()->getCompany())) {
                $consumer->setCompany($this->getCompanyData($quote));
            } else {
                $consumer->setPrivatePerson($this->getPrivatePersonData($quote));
            }
        }

        return $consumer;
    }

    private function getPhoneNumber(Quote $quote): ConsumerPhoneNumber
    {
        $number = new ConsumerPhoneNumber();
        if(!$quote->isVirtual()){
          $address = $quote->getShippingAddress();
          $phone = $quote->getShippingAddress()->getTelephone();
        } else{
          $address = $quote->getBillingAddress();
          $phone = $quote->getBillingAddress()->getTelephone();
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneNumberObject = $phoneUtil->parse(
                $phone,
                $address->getCountryId()
            );
        } catch (NumberParseException) {
            // @TODO log error to investigate issue
            return $number;
        }
            
        $number->setPrefix('+' . $phoneNumberObject->getCountryCode());
        $number->setNumber($phoneNumberObject->getNationalNumber());

        return $number;
    }

    private function getPrivatePersonData(Quote $quote): ConsumerPrivatePerson
    {
        $person = new ConsumerPrivatePerson();
        if (!$quote->isVirtual()) {
            $person->setFirstName($quote->getShippingAddress()->getFirstname());
            $person->setLastName($quote->getShippingAddress()->getLastname());
        } else {
            $person->setFirstName($quote->getBillingAddress()->getFirstname());
            $person->setLastName($quote->getBillingAddress()->getLastname());
        }

        return $person;
    }

    private function getCompanyData(Quote $quote): ConsumerCompany
    {
        $company = new ConsumerCompany();
        if (!$quote->isVirtual()) {
            $company->setName($quote->getShippingAddress()->getCompany());
            $company->setContact($quote->getShippingAddress()->getFirstname(), $quote->getShippingAddress()->getLastname());
        } else {
            $company->setName($quote->getBillingAddress()->getCompany());
            $company->setContact($quote->getBillingAddress()->getFirstname(), $quote->getBillingAddress()->getLastname());
        }

        return $company;
    }

    private function getAddressData(Quote $quote): ConsumerAddress
    {
        $shippingAddress = $quote->getShippingAddress();

        $city = $shippingAddress->getCity();
        $address1 = $shippingAddress->getStreetLine(1);
        $postCode = $shippingAddress->getPostcode();

        if (! $city || !$address1 || !$postCode) {
            throw new \Exception('Address data is missing');
        }

        if (!empty($address1)) {
            if (strlen($address1) > 128) {
                $address1 = substr($address1, 0, 128);
            }
        }

        $shippingAddressLine2 = $shippingAddress->getStreetLine(2);
        if ($shippingAddressLine2 !== '' && strlen($shippingAddressLine2) > 128) {
            $shippingAddressLine2 = substr($shippingAddressLine2, 0, 128);
        }

        $paymentShippingAddress = new ConsumerAddress();
        $paymentShippingAddress->setCity($city);
        $paymentShippingAddress->setAddressLine1($address1);
        $paymentShippingAddress->setAddressLine2($shippingAddressLine2);
        $paymentShippingAddress->setPostalCode($postCode);

        // Country must be in iso3 format
        $localeModel = $this->localeFactory->create();
        $iso3Country = $localeModel->getIso3CountryCode($shippingAddress->getCountryId());
        $paymentShippingAddress->setCountry($iso3Country);

        return $paymentShippingAddress;
    }

    private function getBillingAddressData(Quote $quote): ConsumerAddress
    {
        $shippingAddress = $quote->getBillingAddress();

        $city = $shippingAddress->getCity();
        $address1 = $shippingAddress->getStreetLine(1);
        $postCode = $shippingAddress->getPostcode();

        if (! $city || !$address1 || !$postCode) {
            throw new \Exception('Address data is missing');
        }

        if (!empty($address1)) {
            if (strlen($address1) > 128) {
                $address1 = substr($address1, 0, 128);
            }
        }

        $shippingAddressLine2 = $shippingAddress->getStreetLine(2);
        if ($shippingAddressLine2 !== '' && strlen($shippingAddressLine2) > 128) {
            $shippingAddressLine2 = substr($shippingAddressLine2, 0, 128);
        }

        $paymentShippingAddress = new ConsumerAddress();
        $paymentShippingAddress->setCity($city);
        $paymentShippingAddress->setAddressLine1($address1);
        $paymentShippingAddress->setAddressLine2($shippingAddressLine2);
        $paymentShippingAddress->setPostalCode($postCode);

        // Country must be in iso3 format
        $localeModel = $this->localeFactory->create();
        $iso3Country = $localeModel->getIso3CountryCode($shippingAddress->getCountryId());
        $paymentShippingAddress->setCountry($iso3Country);

        return $paymentShippingAddress;
    }
}

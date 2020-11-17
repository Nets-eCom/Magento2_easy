<?php declare(strict_types=1);

namespace Dibs\EasyCheckout\Model\Quote;

use Dibs\EasyCheckout\Model\Client\DTO\Payment\Consumer;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerPhoneNumber;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerPrivatePerson;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerShippingAddress;
use Magento\Quote\Model\Quote;

/**
 * Class ConsumerDataProvider
 *
 * @package Dibs\EasyCheckout\Model\Quote
 */
class ConsumerDataProvider
{
    /**
     * @var Quote
     */
    private $quote;

    /**
     * @param Quote $quote
     *
     * @return Consumer
     * @throws \Exception
     */
    public function getFromQuote(Quote $quote) : Consumer
    {
        $this->quote = $quote;

        $consumer = new Consumer();
        $consumer->setReference($quote->getCustomerId());

        $consumer->setShippingAddress($this->getAddressData());
        $consumer->setPhoneNumber($this->getPhoneNumber());
        $consumer->setEmail($quote->getBillingAddress()->getEmail());
        $consumer->setPrivatePerson($this->getPrivatePersonData());

        return $consumer;
    }

    /**
     * @return ConsumerPhoneNumber
     * @throws \Exception
     */
    private function getPhoneNumber() : ConsumerPhoneNumber
    {
        $number = new ConsumerPhoneNumber();
        $phone = $this->quote->getShippingAddress()->getTelephone();

        $matches = [];
        if ($phone && preg_match_all('^\+(45|46|358|47)(\d{8,15})$^', $phone, $matches)) {
            $number->setPrefix(isset($matches[1][0]) ? '+' . $matches[1][0] : null);
            $number->setNumber($matches[2][0] ?? null);
        } else {
            $number->setNumber($phone);
        }

        if (empty($number->getPhoneNumber()) || empty($number->getPhoneNumber())) {
            throw new \Exception('Missing phone data');
        }

        return $number;
    }

    /**
     * @return ConsumerPrivatePerson
     */
    private function getPrivatePersonData() : ConsumerPrivatePerson
    {
        $person = new ConsumerPrivatePerson();
        $person->setFirstName($this->quote->getShippingAddress()->getFirstname());
        $person->setLastName($this->quote->getShippingAddress()->getLastname());

        return $person;
    }

    /**
     * @return ConsumerShippingAddress
     * @throws \Exception
     */
    private function getAddressData()
    {
        $shippingAddress = $this->quote->getShippingAddress();
        $city = $shippingAddress->getCity();
        $addressLine1 = $shippingAddress->getStreetLine(1);

        if (empty($city) || empty($addressLine1)) {
            throw new \Exception('Address data is missing');
        }

        $city = $shippingAddress->getCity();
        $address1 = $shippingAddress->getStreetLine(1);
        $postCode = preg_replace('/\s+/','',$shippingAddress->getPostcode());

        if (! $city || !$address1 || !$postCode) {
            throw new \Exception('Address data is missing');
        }

        $paymentShippingAddress = new ConsumerShippingAddress();
        $paymentShippingAddress->setCity($city);
        $paymentShippingAddress->setAddressLine1($addressLine1);
        $paymentShippingAddress->setAddressLine2($shippingAddress->getStreetLine(2));
        $paymentShippingAddress->setCountry($this->getExtendedCountry($shippingAddress->getCountryId()));
        $paymentShippingAddress->setPostalCode($postCode);

        return $paymentShippingAddress;
    }

    /**
     * @param $countryId
     *
     * @return string
     */
    private function getExtendedCountry($countryId)
    {
        $countries = [
            'SE' => 'SWE',
            'NO' => 'NOR',
            'FI' => 'FIN',
            'DK' => 'DEN'
        ];

        return $countries[$countryId] ?? $countryId;
    }
}

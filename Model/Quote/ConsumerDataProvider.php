<?php declare(strict_types=1);

namespace Dibs\EasyCheckout\Model\Quote;

use Dibs\EasyCheckout\Model\Client\DTO\Payment\Consumer;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerPhoneNumber;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerPrivatePerson;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerShippingAddress;
use Magento\Quote\Model\Quote;
use Dibs\EasyCheckout\Model\Dibs\LocaleFactory;

class ConsumerDataProvider
{
    /**
     * @var LocaleFactory
     */
    private $localeFactory;

    public function __construct(
        LocaleFactory $localeFactory
    ) {
        $this->localeFactory = $localeFactory;
    }

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var string[]
     */
    protected $prefixes = [
        'SE' => '+46',
        'DK' => '+45',
        'FI' => '+358',
        'NO' => '+47',
        'AT' => '+43',
    ];

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
        $address = $this->quote->getShippingAddress();
        $phone = $this->quote->getShippingAddress()->getTelephone();
        $string = str_replace([' ', '-', '(', ')'], '', $phone);

        $matches = [];
        preg_match_all('/^(\+)?(45|46|358|47)?(\d{8,10})$/', $string, $matches);
        $prefix = $this->prefixes[$address->getCountryId()] ?? null;
        if (empty($matches[3][0]) || !$prefix) {
            throw new \Exception('Missing phone data');
        }

        $number->setPrefix($prefix);
        $number->setNumber($matches[3][0]);

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
     */
    private function getAddressData()
    {
        $shippingAddress = $this->quote->getShippingAddress();

        $city = $shippingAddress->getCity();
        $address1 = $shippingAddress->getStreetLine(1);
        $postCode = $shippingAddress->getPostcode();

        if (! $city || !$address1 || !$postCode) {
            throw new \Exception('Address data is missing');
        }

        if (strlen($address1) > 128) {
            $address1 = substr($address1, 0, 128);
        }

        $shippingAddressLine2 = '';
        if (strlen($shippingAddress->getStreetLine(2)) > 128) {
            $shippingAddressLine2 = substr($shippingAddress->getStreetLine(2), 0, 128);
        }

        $paymentShippingAddress = new ConsumerShippingAddress();
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

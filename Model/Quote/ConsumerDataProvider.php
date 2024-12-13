<?php declare(strict_types=1);

namespace Dibs\EasyCheckout\Model\Quote;

use Dibs\EasyCheckout\Model\Client\DTO\Payment\Consumer;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerPhoneNumber;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerPrivatePerson;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerCompany;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerAddress;
use Magento\Quote\Model\Quote;
use Dibs\EasyCheckout\Model\Dibs\LocaleFactory;
use Dibs\EasyCheckout\Helper\Data;

class ConsumerDataProvider
{
    private \Dibs\EasyCheckout\Model\Dibs\LocaleFactory $localeFactory;

    /**
     * @var \Dibs\EasyCheckout\Helper\Data $helper
     */
    protected $helper;

    public function __construct(
        LocaleFactory $localeFactory,
        Data $helper

    ) {
        $this->localeFactory = $localeFactory;
        $this->helper = $helper;
    }

    private ?\Magento\Quote\Model\Quote $quote = null;

    /**
     * @var string[]
     */
    protected $prefixes = [
        'SE' => '+46',
        'DK' => '+45',
        'FI' => '+358',
        'NO' => '+47',
        'AT' => '+43',
        'US' => '+1',
        'UK' => '+44',
        'DE' => '+49',
        'ES' => '+34',
        'FR' => '+33',
        'BR' => '+55',
        'UA' => '+380',
        'NL' => '+31',
        'PL' => '+48',
        'IT' => '+39',
        'GB' => '+44',
		'CH' => '+41',
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
        
        if(!$quote->isVirtual()){

          $consumer = new Consumer();
          $consumer->setReference($quote->getCustomerId());
  
          $consumer->setShippingAddress($this->getAddressData());
  	      //If phone number is not empty
          if($quote->getShippingAddress()->getTelephone()) {
              $consumer->setPhoneNumber($this->getPhoneNumber());
          }
  	      //$consumer->setPhoneNumber($this->getPhoneNumber());
          $consumer->setEmail($quote->getBillingAddress()->getEmail());
          $weHandleConsumer = $this->helper->doesHandleCustomerData();
          if ($weHandleConsumer && !empty($quote->getShippingAddress()->getCompany())) {
              $consumer->setCompany($this->getCompanyData());
          } else {
              $consumer->setPrivatePerson($this->getPrivatePersonData());
          }

          if ($this->helper->getSplitAddresses() && !$quote->getShippingAddress()->getSameAsBilling()) {
              $consumer->setBillingAddress($this->getBillingAddressData());
          }
        } else{
          $consumer = new Consumer();
          $consumer->setReference($quote->getCustomerId());
  
          $consumer->setShippingAddress($this->getBillingAddressData());
  	      //If phone number is not empty
          if($quote->getBillingAddress()->getTelephone()) {
              $consumer->setPhoneNumber($this->getPhoneNumber());
          }
  	      //$consumer->setPhoneNumber($this->getPhoneNumber());
          $consumer->setEmail($quote->getBillingAddress()->getEmail());
          $weHandleConsumer = $this->helper->doesHandleCustomerData();
          if ($weHandleConsumer && !empty($quote->getBillingAddress()->getCompany())) {
              $consumer->setCompany($this->getCompanyData());
          } else {
              $consumer->setPrivatePerson($this->getPrivatePersonData());
          }
        }

        return $consumer;
    }

    /**
     * @return ConsumerPhoneNumber
     * @throws \Exception
     */
    private function getPhoneNumber() : ConsumerPhoneNumber
    {
        $number = new ConsumerPhoneNumber();
        if(!$this->quote->isVirtual()){
          $address = $this->quote->getShippingAddress();
          $phone = $this->quote->getShippingAddress()->getTelephone();
        } else{
          $address = $this->quote->getBillingAddress();
          $phone = $this->quote->getBillingAddress()->getTelephone();
        }
        $string = str_replace([' ', '-', '(', ')'], '', $phone);

        $matches = [];
        preg_match_all('/^(\+)?(45|46|358|47|43|1|44|49|34|33|55|380|31|48|39|41)?(\d{8,12})$/', $string, $matches);
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
        if(!$this->quote->isVirtual()){
          $person->setFirstName($this->quote->getShippingAddress()->getFirstname());
          $person->setLastName($this->quote->getShippingAddress()->getLastname());
        } else{
          $person->setFirstName($this->quote->getBillingAddress()->getFirstname());
          $person->setLastName($this->quote->getBillingAddress()->getLastname());
        }

        return $person;
    }

    /**
     * @return ConsumerCompany
     */
    private function getCompanyData() : ConsumerCompany
    {
        $company = new ConsumerCompany();
        if(!$this->quote->isVirtual()){
          $company->setName($this->quote->getShippingAddress()->getCompany());
          $company->setContact($this->quote->getShippingAddress()->getFirstname(), $this->quote->getShippingAddress()->getLastname());
        } else{
          $company->setName($this->quote->getBillingAddress()->getCompany());
          $company->setContact($this->quote->getBillingAddress()->getFirstname(), $this->quote->getBillingAddress()->getLastname());
        }
        //$company->setContact($this->quote->getShippingAddress()->getLastname());

        return $company;
    }

    /**
     * @return ConsumerAddress
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

        if (!empty($address1)) {
            if (strlen($address1) > 128) {
                $address1 = substr($address1, 0, 128);
            }
        }

        $shippingAddressLine2 = '';
        if (!empty($shippingAddress->getStreetLine(2))) {
            if (strlen($shippingAddress->getStreetLine(2)) > 128) {
                $shippingAddressLine2 = substr($shippingAddress->getStreetLine(2), 0, 128);
            }
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
    
    private function getBillingAddressData()
    {
        $shippingAddress = $this->quote->getBillingAddress();

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

        $shippingAddressLine2 = '';
        if (!empty($shippingAddress->getStreetLine(2))) {
            if (strlen($shippingAddress->getStreetLine(2)) > 128) {
                $shippingAddressLine2 = substr($shippingAddress->getStreetLine(2), 0, 128);
            }
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

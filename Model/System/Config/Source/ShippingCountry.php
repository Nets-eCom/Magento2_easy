<?php
namespace Dibs\EasyCheckout\Model\System\Config\Source;

class ShippingCountry implements \Magento\Framework\Option\ArrayInterface
{


    /** @var $_country \Magento\Directory\Model\Config\Source\Country */
    protected $_country;

    /** @var \Dibs\EasyCheckout\Model\Dibs\Locale $_locale */
    protected $_locale;

    private array $_countryMap = [];

    public function __construct(
        \Magento\Directory\Model\Config\Source\Country $country,
        \Dibs\EasyCheckout\Model\Dibs\Locale $locale
    )
    {
        $this->_locale = $locale;
        $this->_country = $country;
    }


    public function toOptionArray($isMultiselect=false)
    {
        $this->initCountryMap();

        $locales = $this->_locale->getAllowedShippingCountries();
        $return = array();

        if(!$isMultiselect) {
            $return[] = array('value'=>'', 'label'=> '');
        }

        $mappedCountries = [];
        foreach($locales as $iso3Code => $countryCode) {
            $label = $this->getCountryLabelByCode($countryCode);
            if ($label === null) {
                $label = $countryCode;
            }

            $mappedCountries[$label] = $iso3Code;
        }

        // sort
        $sortedCountries = array_keys($mappedCountries);
        asort($sortedCountries);

        foreach ($sortedCountries as $country) {
            $return[] = array(
                'value'=>$mappedCountries[$country],
                'label'=>$country
            );
        }

        return $return;
    }

    private function initCountryMap()
    {
        $this->_countryMap = [];
        $countries = $this->_country->toOptionArray(false);
        foreach($countries as $country) {
            $this->_countryMap[$country['value']] = $country['label'];
        }

        return $this;
    }

    private function getCountryLabelByCode($countryCode)
    {
        if (array_key_exists($countryCode, $this->_countryMap)) {
            return $this->_countryMap[$countryCode];
        }

        return null;
    }
}
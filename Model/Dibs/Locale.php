<?php

namespace Dibs\EasyCheckout\Model\Dibs;

class Locale
{

    /**
     *  Allowed Locale Variables, see:
     *  https://tech.dibspayment.com/easy/api/datastring-parameters
     */

    /**
     * Allowed Consumer/Customer Types
     * @var array $allowedConsumerTypes
     */
    protected $allowedConsumerTypes = [
        "B2C", "B2B"
    ];

    /**
     * Swedish, Norway, Danish, Euro and Dollar
     * @var array $allowedCurrencies
     */
    protected $allowedCurrencies = [
        "SEK", "NOK", "DKK", "EUR", "USD"
    ];

    /**
     * Iso Codes
     * @var array $allowedShippingCountries
     */
    protected $allowedShippingCountries = [
        "BIH"  => "BA",
        "VAT"  => "VA",
        "BLR"  => "BY",
        "ALB"  => "AL",
        "AND"  => "AD",
        "ARM"  => "AM",
        "AUT"  => "AT",
        "AZE"  => "AZ",
        "BEL"  => "BE",
        "BGR"  => "BG",
        "MLT"  => "MT",
        "MKD"  => "MK",
        "HRV"  => "HR",
        "CYP"  => "CY",
        "CZE"  => "CZ",
        "DNK"  => "DK",
        "EST"  => "EE",
        "FIN"  => "FI",
        "FRA"  => "FR",
        "GEO"  => "GE",
        "DEU"  => "DE",
        "GRC"  => "GR",
        "HUN"  => "HU",
        "ISL"  => "IS",
        "ITA"  => "IT",
        "KAZ"  => "KZ",
        "LVA"  => "LV",
        "LTU"  => "LT",
        "LUX"  => "LU",
        "MCO"  => "MC",
        "MDA" => "MD",
        "MNE" => "ME",
        "NLD"  => "NL",
        "NOR"  => "NO",
        "POL"  => "PL",
        "PRT"  => "PT",
        "RUS"  => "RU",
        "SMR"  => "SM",
        "SVK"  => "SK",
        "SVN"  => "SI",
        "SRB"  => "RS",
        "ROU" => "RO",
        "ESP"  => "ES",
        "SWE"  => "SE",
        "CHE"  => "CH",
        "TUR"  => "TR",
        "UKR"  => "UA",
        "GBR"  => "GB",
        "IRL"  => "IE",
        "LIE"  => "LI",
    ];

    protected $localeMap = [
        "SE" => "sv-SE",
        "NO" => "nb-NO",
        "DK" => "da-DK",
        "DE" => "de-DE",
        "PL" => "pl-PL",
        "FR" => "fr-FR",
        "ES" => "es-ES",
        "IT" => "it-IT",
        "NL" => "nl-NL",
        "FI" => "fi-FI",
        "AT" => "de-DE"
    ];

    public function getCountryIdByIso3Code($iso3)
    {
        foreach ($this->allowedShippingCountries as $key => $countryId) {
            if ($key === $iso3) {
                return $countryId;
            }
        }

        // we return it back if we cant find anything... We should throw an exception in the future!
        return $iso3;
    }

    /**
     * @return array
     */
    public function getAllowedConsumerTypes()
    {
        return $this->allowedConsumerTypes;
    }

    /**
     * @return array
     */
    public function getAllowedCurrencies()
    {
        return $this->allowedCurrencies;
    }


    /**
     * @param $countryCode string
     * @return string
     */
    public function getLocaleByCountryCode($countryCode)
    {
        return $this->localeMap[$countryCode] ?? 'en-GB';
    }

    /**
     * Get iso3 country code for provided iso2 country code
     *
     * @param string $countryCode The iso2 country code
     * @return string
     */
    public function getIso3CountryCode($countryCode)
    {
        $allowedCountries = array_flip($this->allowedShippingCountries);
        return $allowedCountries[$countryCode] ?? '';
    }
}

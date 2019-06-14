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
     * Swedish, Norway, Danish Kronor
     * @var array $allowedCurrencies
     */
    protected $allowedCurrencies = [
      "SEK","NOK","DKK"
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
     * @return array
     */
    public function getAllowedShippingCountries()
    {
        return $this->allowedShippingCountries;
    }


}
<?php

namespace Dibs\EasyCheckout\Model\Dibs;
use Magento\Framework\Locale\Resolver;

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
        "DKK", "SEK", "NOK", "EUR", "USD", "GBP", "CHF", "PLN"
    ];

    /**
     * Iso Codes
     * @var array $allowedShippingCountries
     */
    protected $allowedShippingCountries = [
	"AFG" => "FA",
	"ALB" => "AL",
	"DZA" => "DZ",
	"AND" => "AD",
	"AGO" => "AO",
	"ATG" => "AG",
	"ARG" => "AR",
	"ARM" => "AM",
	"AUS" => "AU",
	"AUT" => "AT",
	"AZE" => "AZ",
	"BHS" => "BS",
	"BHR" => "BH",
	"BGD" => "BD",
	"BRB" => "BB",
	"BLR" => "BY",
	"BEL" => "BE",
	"BLZ" => "BZ",
	"BEN" => "BJ",
	"BTN" => "BT",
	"BOL" => "BO",
	"BIH" => "BA",
	"BWA" => "BW",
	"BRA" => "BR",
	"BGR" => "BG",
	"BFA" => "BF",
	"BDI" => "BI",
	"KHM" => "KH",
	"CMR" => "CM",
	"CAN" => "CA",
	"CPV" => "CV",
	"CAF" => "CF",
	"TCD" => "TD",
	"CHL" => "CL",
	"CHN" => "CN",
	"COL" => "CO",
	"COM" => "KM",
	"COG" => "CG",
	"COD" => "CD",
	"CRI" => "CR",
	"CIV" => "CI",
	"HRV" => "HR",
	"CUB" => "CU",
	"CYP" => "CY",
	"CZE" => "CZ",
	"DNK" => "DK",
	"DJI" => "DJ",
	"DMA" => "DM",
	"DOM" => "DO",
	"ECU" => "EC",
	"EGY" => "EG",
	"SLV" => "SV",
	"GNQ" => "GQ",
	"ERI" => "ER",
	"EST" => "EE",
	"ETH" => "ET",
	"FJI" => "FJ",
	"FIN" => "FI",
	"FRA" => "FR",
	"GAB" => "GA",
	"GMB" => "GM",
	"GEO" => "GE",
	"DEU" => "DE",
	"GHA" => "GH",
	"GRC" => "GR",
	"GRL" => "GL",
	"GRD" => "GD",
	"GTM" => "GT",
	"GNB" => "GW",
	"GIN" => "GN",
	"GUY" => "GY",
	"HTI" => "HT",
	"HND" => "HN",
	"HKG" => "HK",
	"HUN" => "HU",
	"ISL" => "IS",
	"IND" => "IN",
	"IDN" => "ID",
	"IRN" => "IR",
	"IRQ" => "IQ",
	"IRL" => "IE",
	"ISR" => "IL",
	"ITA" => "IT",
	"JAM" => "JM",
	"JPN" => "JP",
	"JOR" => "JO",
	"KAZ" => "KZ",
	"KEN" => "KE",
	"KIR" => "KI",
	"KWT" => "KW",
	"KGZ" => "KG",
	"LAO" => "LA",
	"LVA" => "LV",
	"LBN" => "LB",
	"LSO" => "LS",
	"LBR" => "LR",
	"LBY" => "LY",
	"LIE" => "LI",
	"LTU" => "LT",
	"LUX" => "LU",
	"MKD" => "MK",
	"MDG" => "MG",
	"MWI" => "MW",
	"MYS" => "MY",
	"MDV" => "MV",
	"MLI" => "ML",
	"MLT" => "MT",
	"MHL" => "MH",
	"MRT" => "MR",
	"MUS" => "MU",
	"MEX" => "MX",
	"FSM" => "FM",
	"MDA" => "MA",
	"MCO" => "MX",
	"MNG" => "MN",
	"MNE" => "ME",
	"MAR" => "MA",
	"MOZ" => "MZ",
	"MMR" => "MM",
	"NAM" => "NA",
	"NRU" => "NR",
	"NPL" => "NP",
	"NLD" => "NL",
	"NZL" => "NZ",
	"NIC" => "NI",
	"NER" => "NE",
	"NGA" => "NG",
	"PRK" => "KP",
	"NOR" => "NO",
	"OMN" => "OM",
	"PAK" => "PK",
	"PLW" => "PW",
	"PAN" => "PA",
	"PNG" => "PG",
	"PRY" => "PY",
	"PER" => "PE",
	"PHL" => "PH",
	"POL" => "PL",
	"PRT" => "PT",
	"QAT" => "QA",
	"ROU" => "RO",
	"RUS" => "RU",
	"RWA" => "RW",
	"WSM" => "WS",
	"SMR" => "SM",
	"STP" => "ST",
	"SAU" => "SA",
	"SEN" => "SN",
	"SRB" => "RS",
	"SYC" => "SC",
	"SLE" => "SL",
	"SGP" => "SG",
	"SVK" => "SK",
	"SVN" => "SI",
	"SLB" => "SB",
	"SOM" => "SO",
	"ZAF" => "ZA",
	"KOR" => "KR",
	"SSD" => "SS",
	"ESP" => "ES",
	"LKA" => "LK",
	"KNA" => "KN",
	"LCA" => "LC",
	"VCT" => "VC",
	"SDN" => "SD",
	"SUR" => "SR",
	"SWZ" => "SZ",
	"SWE" => "SE",
	"CHE" => "CH",
	"SYR" => "SY",
	"TWN" => "TW",
	"TJK" => "TJ",
	"TZA" => "TZ",
	"THA" => "TH",
	"TLS" => "TL",
	"TGO" => "TG",
	"TON" => "TO",
	"TTO" => "TT",
	"TUN" => "TN",
	"TUR" => "TR",
	"TKM" => "TM",
	"TUV" => "TV",
	"UGA" => "UG",
	"GBR" => "GB",
	"UKR" => "UA",
	"ARE" => "AE",
	"URY" => "UY",
	"USA" => "US",
	"UZB" => "UZ",
	"VUT" => "VU",
	"VAT" => "VA",
	"VEN" => "VE",
	"VNM" => "VN",
	"YEM" => "YE",
	"ZMB" => "ZM",
	"ZWE" => "ZW"
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
        "AT" => "de-AT"
    ];
    
    /**
     * @var Resolver
     */
    private $localeResolver;

    public function __construct(
        Resolver $localeResolver
    ) {
        $this->localeResolver = $localeResolver;
    }

    public function getCurrentLocale()
    {
        $currentLocaleCode = $this->localeResolver->getLocale(); // fr_CA
        $languageCode = str_replace('_', '-', $currentLocaleCode);
        return $languageCode;
    }
    

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
        //return $this->localeMap[$countryCode] ?? 'en-GB';
        $ctrCode = $this->getCurrentLocale();
	return $ctrCode ?? 'en-GB';
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

<?php

namespace Dibs\EasyCheckout\Helper;

use Magento\Directory\Model\AllowedCountries;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Cart
 * @package Dibs\EasyCheckout\Helper
 */
class Cart extends AbstractHelper
{

    /**
     * dibs_easycheckout/crosssell/
     */
    const XML_PATH_CROSSSELL = 'dibs_easycheckout/crosssell/';
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Directory\Model\AllowedCountries
     */
    protected $allowedCountryModel;

    /**
     * @var CountryFactory
     */
    protected $countryFactory;

    /**
     * Cart constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param AllowedCountries $allowedCountryModel
     * @param CountryFactory $countryFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        AllowedCountries $allowedCountryModel,
        CountryFactory $countryFactory
    )
    {
        $this->storeManager = $storeManager;
        $this->allowedCountryModel = $allowedCountryModel;
        $this->countryFactory = $countryFactory;
        parent::__construct($context);
    }

    /**
     * @return Current Currency code
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * @return Current Currency symbol
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrencySymbol()
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCurrencySymbol();
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function isDisplayCrosssell($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CROSSSELL . 'display_crosssell',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getNumberOfCrosssellProducts($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CROSSSELL . 'crosssell_limit',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @return array
     */
    public function getAllowedCountriesNames()
    {
        $allowedCountriesNames = [];
        $allowedCountries = $this->allowedCountryModel->getAllowedCountries();
        foreach ($allowedCountries as $allowedCountryCode) {
            $allowedCountriesNames[$allowedCountryCode] = $this->countryFactory->create()->loadByCode($allowedCountryCode)->getName();
        }
        return $allowedCountriesNames;
    }

    /**
     * @return array
     */
    public function getAllowedCountriesList()
    {
        return $this->allowedCountryModel->getAllowedCountries();
    }

    /**
     * @param $countryId
     * @return Country Name
     */
    public function getCountryNameByCode($countryId)
    {
        return $this->countryFactory->create()->loadByCode($countryId)->getName();
    }
}
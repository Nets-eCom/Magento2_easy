<?php
namespace Dibs\EasyCheckout\Helper;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{


    const XML_PATH_CONNECTION     = 'dibs_easycheckout/connection/';
    const API_BASE_URL_TEST = "https://test.api.dibspayment.eu";
    const API_BASE_URL_LIVE = "https://api.dibspayment.eu";

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;


    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     *
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }


    public function getApiSecretKey($store = null) {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CONNECTION.'secret_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getApiCheckoutKey($store = null) {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CONNECTION.'checkout_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }


    public function isEnabled($store = null) {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CONNECTION.'enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function isTestMode($store = null) {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CONNECTION.'test_mode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }


    public function getApiUrl($store = null){
        if ($this->isTestMode($store)) {
            return self::API_BASE_URL_TEST;
        } else {
            return self::API_BASE_URL_LIVE;
        }
    }



}
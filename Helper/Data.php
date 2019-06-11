<?php
namespace Dibs\EasyCheckout\Helper;


use Magento\Quote\Model\Quote;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const COOKIE_CART_CTRL_KEY = 'DibsCartCtrlKey';


    const XML_PATH_CONNECTION  = 'dibs_easycheckout/connection/';
    const XML_PATH_SETTINGS = 'dibs_easycheckout/settings/';

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
            self::XML_PATH_CONNECTION.'enabled',
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

    public function chargeDirectly($store = null) {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SETTINGS.'charge_directly',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    protected function _replaceCheckout($store = null) {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SETTINGS.'replace_checkout',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function replaceCheckout($store = null)
    {
        return $this->isEnabled($store) && $this->_replaceCheckout($store);
    }

    public function registerCustomerOnCheckout($store = null)
    {
        return false; // TODO settings :)
    }


    // TODO
    public function canCapture($store = null)
    {
        return true;
    }


    // TODO
    public function canCapturePartial($store = null)
    {
        return true;
    }

    /** Helpers */
    public function getCheckoutPath($path = null)
    {
        if (empty($path)) {
            return 'easycheckout';
        }

        return 'easycheckout/order/' . trim(ltrim($path, '/'));
    }

    public function getCheckoutUrl($path = null, $params = [])
    {
        if (empty($path)) {
            return $this->_getUrl('easycheckout', $params);
        }
        return $this->_getUrl($this->getCheckoutPath($path), $params);
    }

    public function getTermsUrl($store = null)
    {

        //if there are multiple pages with same url key; magento will generate options with key|id
        $url = explode('|', (string)$this->getStoreConfig(self::XML_PATH_SETTINGS . 'terms_url', $store));
        return $url[0];
    }



    public function getCartCtrlKeyCookieName()
    {
        return self::COOKIE_CART_CTRL_KEY;
    }

    public function subscribeNewsletter(Quote $quote)
    {
        if ($quote->getPayment()) {
            $status = (int)$quote->getPayment()->getAdditionalInformation("dibs_easy_checkout_newsletter");
        } else {
            $status = null;
        }

        if ($status) { //when is set (in quote) is -1 for NO, 1 for Yes
            return $status>0;
        } else { //get default value
            return false; //  todo load from settings if we should use newsletter
        }
    }


    /**
     * This function returns a hash, we will use it to check for changes in the quote!
     * @param Quote $quote
     * @return string
     */
    public function generateHashSignatureByQuote(Quote $quote)
    {
        $shippingMethod = null;
        $countryId = null;
        if (!$quote->isVirtual()) {
            $shippingAddress = $quote->getShippingAddress();
            $countryId = $shippingAddress->getCountryId();
            $shippingMethod = $shippingAddress->getShippingMethod();
        }

        $billingAddress = $quote->getBillingAddress();
        $info = [
            'currency'=> $quote->getQuoteCurrencyCode(),
            'shipping_method' => $shippingMethod,
            'shipping_country' => $countryId,
            'billing_country' =>$billingAddress->getCountryId(),
            'payment' => $quote->getPayment()->getMethod(),
            'subtotal'=> sprintf("%.2f", round($quote->getBaseSubtotal(), 2)), //store base (currency will be set in checkout)
            'total'=> sprintf("%.2f", round($quote->getBaseGrandTotal(), 2)),  //base grand total
            'items'=> []
        ];

        foreach ($quote->getAllVisibleItems() as $item) {
            $info['items'][$item->getId()] = sprintf("%.2f", round($item->getQty()*$item->getBasePriceInclTax(), 2));
        }
        ksort($info['items']);
        return md5(serialize($info));
    }

    public function getHeaderText()
    {
        return ""; // todo translate or get from settings =)
    }

    public function getStoreConfig($path, $store = null) {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getStoreConfigFlag($path,$store = null) {
        return $this->scopeConfig->isSetFlag(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

}
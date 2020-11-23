<?php
namespace Dibs\EasyCheckout\Helper;

use Magento\Quote\Model\Quote;

/**
 * Class Data
 * @package Dibs\EasyCheckout\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Cart Cookie name. Will be used to check if the cart was updated
     */
    const COOKIE_CART_CTRL_KEY = 'DibsCartCtrlKey';

    /**
     * Dibs System Settings, Connection group
     */
    const XML_PATH_CONNECTION  = 'dibs_easycheckout/connection/';

    /**
     * Dibs System Settings, settings group
     */
    const XML_PATH_SETTINGS = 'dibs_easycheckout/settings/';

    const XML_PATH_SETTINGS_INVOICE = 'dibs_easycheckout/invoice/';

    /**
     * Dibs System Settings, layout group
     */
    const XML_PATH_LAYOUT = 'dibs_easycheckout/layout/';

    /**
     * Dibs Payment, test API url
     */
    const API_BASE_URL_TEST = "https://test.api.dibspayment.eu";

    /**
     * Dibs Payment, live API url
     */
    const API_BASE_URL_LIVE = "https://api.dibspayment.eu";

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /** @var \Dibs\EasyCheckout\Model\Dibs\Locale $dibsLocale */
    protected $dibsLocale;

    protected $allowedCountryModel;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Dibs\EasyCheckout\Model\Dibs\Locale $locale
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Cms\Api\GetPageByIdentifierInterface $_cmsPage
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Dibs\EasyCheckout\Model\Dibs\Locale $locale,
        \Magento\Directory\Model\AllowedCountries $allowedCountryModel,
        \Magento\Framework\App\State $state,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Cms\Api\GetPageByIdentifierInterface $_cmsPage,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->dibsLocale = $locale;
        $this->storeManager = $storeManager;
        $this->allowedCountryModel = $allowedCountryModel;
        $this->state = $state;
        $this->orderRepository = $orderRepository;
        $this->_cmsPage = $_cmsPage;
        $this->serializer = $serializer;

        parent::__construct($context);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getApiSecretKey($store = null)
    {
        if ($this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            // in admin area
            $orderId = $this->_request->getParam('order_id', null);
            if ($orderId) {
                $order = $this->orderRepository->get($orderId);
                $storeId = $order->getStoreId();
                $store = $this->storeManager->getStore($storeId);
            }
        }

        return $this->scopeConfig->getValue(
            self::XML_PATH_CONNECTION . 'secret_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getApiCheckoutKey($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CONNECTION . 'checkout_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CONNECTION . 'enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function addCustomOptionsToItemName($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SETTINGS . 'checkout_add_options_to_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }



    /**
     * @param null $store
     * @return bool
     */
    public function isTestMode($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CONNECTION . 'test_mode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function useInvoiceFee($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SETTINGS_INVOICE . 'use_invoice_fee',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getInvoiceFeeLabel($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SETTINGS_INVOICE . 'invoice_fee_label',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getInvoiceFee($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SETTINGS_INVOICE . 'invoice_fee',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return string
     */
    public function getApiUrl($store = null)
    {
        if ($this->isTestMode($store)) {
            return self::API_BASE_URL_TEST;
        } else {
            return self::API_BASE_URL_LIVE;
        }
    }

    /**
     * @param null $store
     * @return bool
     */
    protected function _replaceCheckout($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SETTINGS . 'replace_checkout',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function replaceCheckout($store = null)
    {
        return $this->isEnabled($store) && $this->_replaceCheckout($store);
    }

    /**
     * @param null $store
     * @return bool
     */
    public function registerCustomerOnCheckout($store = null)
    {
        return $this->getStoreConfigFlag(self::XML_PATH_SETTINGS . 'register_customer', $store);
    }

    /**
     * @param null $store
     * @return bool
     */
    public function canCapture($store = null)
    {
        return $this->getStoreConfigFlag(self::XML_PATH_SETTINGS . 'can_capture', $store);
    }

    /**
     * @param null $store
     * @return bool
     */
    public function canCapturePartial($store = null)
    {
        return $this->getStoreConfigFlag(self::XML_PATH_SETTINGS . 'can_capture_partial', $store);
    }

    /** Helpers */
    public function getCheckoutPath($path = null)
    {
        if (empty($path)) {
            return 'easycheckout';
        }

        return 'easycheckout/order/' . trim(ltrim($path, '/'));
    }

    /**
     * @return string
     */
    public function getSuccessPageUrl()
    {
        return $this->_getUrl('easycheckout/order/success');
    }

    /**
     * @param $quoteId
     * @return string
     */
    public function getWebHookCallbackUrl($quoteId)
    {
        return $this->getCheckoutUrl("WebhookCallback/qid/" . $quoteId);
    }

    /**
     * @param null $store
     * @return string
     */
    public function getWebhookSecret($store = null)
    {
        $secret = $this->scopeConfig->getValue(
            self::XML_PATH_CONNECTION . 'webhook_auth',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        if (!$secret) {
            return null;
        }

        return str_replace("=", "",base64_encode($secret));
    }
    /**
     * @param null $path
     * @param array $params
     * @return string
     */
    public function getCheckoutUrl($path = null, $params = [])
    {
        if (empty($path)) {
            return $this->_getUrl('easycheckout', $params);
        }
        return $this->_getUrl($this->getCheckoutPath($path), $params);
    }

    /**
     * @param null $store
     *
     * @return bool
     */
    public function getWebhookHandleTimeout($store = null)
    {
        return $this->getStoreConfig(self::XML_PATH_CONNECTION . 'webhook_timeout', $store);
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function doesHandleCustomerData($store = null)
    {
        return $this->getStoreConfigFlag(self::XML_PATH_SETTINGS . 'handle_customer_data', $store);
    }

    /**
     * @param null $store
     * @return string
     */
    public function getTermsUrl($store = null)
    {
        //if there are multiple pages with same url key; magento will generate options with key|id
        $url = explode('|', (string)$this->getStoreConfig(self::XML_PATH_SETTINGS . 'terms_url', $store));
        return $this->_getUrl($url[0]);
    }

    /**
     * @param null $store
     * @return string
     */
    public function getPrivacyUrl($store = null)
    {
        //if there are multiple pages with same url key; magento will generate options with key|id
        $url = explode('|', (string)$this->getStoreConfig(self::XML_PATH_SETTINGS . 'privacy_url', $store));

        return $this->_getUrl($url[0]);
    }

    /**
     * @param null $store
     * @return string
     */
    public function getPrivacyLabel($store = null)
    {
        $url = explode('|', (string)$this->getStoreConfig(self::XML_PATH_SETTINGS . 'privacy_url', $store));
        $identifier = $url[0];
        $result = $this->_cmsPage->execute($identifier, $store)->getTitle();

        return $result;
    }

    /**
     * @return string
     */
    public function getCartCtrlKeyCookieName()
    {
        return self::COOKIE_CART_CTRL_KEY;
    }

    /**
     * @param Quote $quote
     * @return bool
     */
    public function subscribeNewsletter(Quote $quote)
    {
        if ($quote->getPayment()) {
            $status = (int)$quote->getPayment()->getAdditionalInformation("dibs_easy_checkout_newsletter");
        } else {
            $status = null;
        }

        if ($status) { //when is set (in quote) is -1 for NO, 1 for Yes
            return $status>0;
        } else {
            //get default value from settings
            return $this->getStoreConfigFlag(self::XML_PATH_SETTINGS . 'newsletter_subscribe', $quote->getStore()->getId());
        }
    }

    public function getDefaultShippingMethod($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SETTINGS . 'default_shipping_method',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getDefaultCountry($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SETTINGS . 'default_country',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }


    /**
     * @param null $store
     * @return array|null
     */
    public function getCountries($store = null)
    {
        return $this->allowedCountryModel->getAllowedCountries(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }


    public function getDefaultConsumerType($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SETTINGS . 'default_customer_type',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getConsumerTypes($store = null)
    {
        $values =  $this->scopeConfig->getValue(
            self::XML_PATH_SETTINGS . 'customer_types',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        return $this->splitStringToArray($values);
    }

    public function getCheckoutFlow($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SETTINGS . 'checkout_flow',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
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
        return hash('sha256', $this->serializer->serialize($info));
    }

    /**
     * @param $path
     * @param null $store
     * @return mixed
     */
    public function getStoreConfig($path, $store = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param $path
     * @param null $store
     * @return bool
     */
    public function getStoreConfigFlag($path, $store = null)
    {
        return $this->scopeConfig->isSetFlag(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getAdditionalBlock($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LAYOUT . 'additional_block',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    protected function splitStringToArray($values)
    {
        return preg_split("#\s*[ ,;]\s*#", $values, null, PREG_SPLIT_NO_EMPTY);
    }
}

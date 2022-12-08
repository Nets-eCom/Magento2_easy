<?php

namespace Dibs\EasyCheckout\Model;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\StoreManagerInterface;

class CheckoutConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{

    const LOGO_DIR = 'payments/logo/';
    /** @var UrlInterface */
    protected $_urlBuilder;

    /** @var Repository */
    protected $assetRepository;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;
    
    protected $_controllerPath = 'easycheckout/order';

    public function __construct(
        UrlInterface $_urlBuilder,
        Repository $assetRepository,
        \Dibs\EasyCheckout\Helper\Data $dibsHelper,
        StoreManagerInterface $storeManager
     )
    {
        $this->_urlBuilder = $_urlBuilder;
        $this->assetRepository = $assetRepository;
        $this->_dibsHelper = $dibsHelper;
        $this->storeManager = $storeManager;
    }

    public function getConfig()
    {
        $output['saveShippingMethodUrl'] = $this->_urlBuilder->getUrl("{$this->_controllerPath}/SaveShippingMethod");
        $output['saveUdcShippingMethodUrl'] = $this->_urlBuilder->getUrl("{$this->_controllerPath}/SaveUdcShipping");
        $output['payment']['logoUrl'] = $this->getLogo();
        $output['payment']['paymentName'] = $this->getPaymentName();
        return $output;
    }
    
    public function getLogo() {
        if(!empty($this->_dibsHelper->getLogo())) {
            $fileUrl = self::LOGO_DIR . $this->_dibsHelper->getLogo();
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            $logoUrl = $mediaUrl . $fileUrl;
        } else {
            $logoUrl = $this->assetRepository->getUrlWithParams('Dibs_EasyCheckout::images/nets_logo.png', []);
        }
        return $logoUrl;
    }
    
    public function getPaymentName() {
        if(!empty($this->_dibsHelper->getPaymentName())) {
            $paymentName = $this->_dibsHelper->getPaymentName();
        } else {
            $paymentName = "Nets Easy Payment";
        }
        return $paymentName;
    }
}
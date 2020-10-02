<?php
namespace Dibs\EasyCheckout\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class MarkCartDirtyObserver implements ObserverInterface
{

    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    protected $helper;

    /** @var \Dibs\EasyCheckout\Model\Checkout */
    protected $dibsOrderHandler;

    /** @var \Magento\Framework\Session\Config\ConfigInterface  */
    protected $sessionConfig;

    /** @var \Magento\Framework\Stdlib\CookieManagerInterface  */
    protected $cookieManager;

    /** @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory  */
    protected $cookieMetadataFactory;

    public function __construct(
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Dibs\EasyCheckout\Model\Checkout $dibsOrderHandler,
        \Magento\Framework\Session\Config\ConfigInterface $sessionConfig,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->helper = $helper;
        $this->dibsOrderHandler = $dibsOrderHandler;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager = $cookieManager;
        $this->sessionConfig = $sessionConfig;
    }

    public function execute(EventObserver $observer)
    {
        if (!$this->helper->isEnabled()) {
            return $this;
        }

        /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $observer->getEvent()->getCart();

        // if we havent started the checkout yet, ignore this!
        $oldHash = $cart->getQuote()->getHashSignature();
        if (!$oldHash) {
            return $this;
        }

        //this is used in thank you page, we do not want that in checkout page, it should not reload the page while we are still in thank you page
        if ($this->dibsOrderHandler->getDoNotMarkCartDirty()) {
            return $this;
        }

        // we set the cookie here!
        try {
            $this->setCookie($this->helper->generateHashSignatureByQuote($cart->getQuote()));
        } catch (\Exception $e) {
            // ignore exception...
        }
    }

    /**
     * @param $cookieValue
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    private function setCookie($cookieValue)
    {
        $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setPath($this->sessionConfig->getCookiePath())
            ->setDomain($this->sessionConfig->getCookieDomain())
            ->setSecure($this->sessionConfig->getCookieSecure())
            ->setHttpOnly(false); // will be used by the browser

        $this->cookieManager->setPublicCookie($this->helper->getCartCtrlKeyCookieName(), $cookieValue, $cookieMetadata);
    }
}

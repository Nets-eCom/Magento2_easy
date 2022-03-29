<?php
namespace Dibs\EasyCheckout\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Dibs\EasyCheckout\Model\Client\DTO\UpdatePaymentReference;
use \Dibs\EasyCheckout\Model\Client\Client;

class OnepageSuccessObserver extends Client implements ObserverInterface
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
        \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi,
        //\Dibs\EasyCheckout\Model\Client\Client $clientApi,
        \Dibs\EasyCheckout\Model\Checkout $dibsOrderHandler,
        \Magento\Framework\Session\Config\ConfigInterface $sessionConfig,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->helper = $helper;
        $this->paymentApi = $paymentApi;
        // $this->clientApi  = $clientApi;
        $this->dibsOrderHandler = $dibsOrderHandler;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager = $cookieManager;
        $this->sessionConfig = $sessionConfig;
    }

    public function execute(EventObserver $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order === null) {
            return;
        }

        $orderId = $order->getIncrementId();
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();
        if ($payment->getMethod() == "dibseasycheckout") {
            $paymentId = $order->getDibsPaymentId();
            $reference = new UpdatePaymentReference();
            $reference->setReference($order->getIncrementId());
            $reference->setCheckoutUrl($this->helper->getCheckoutUrl());
            if ($this->helper->getCheckoutFlow() === "HostedPaymentPage") {
                $payment = $this->paymentApi->getPayment($paymentId);
                $checkoutUrl = $payment->getCheckoutUrl();
                $reference->setCheckoutUrl($checkoutUrl);
            }

            $this->paymentApi->UpdatePaymentReference($reference, $paymentId);
        }
    }
}

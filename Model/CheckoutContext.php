<?php

namespace Dibs\EasyCheckout\Model;


class CheckoutContext
{
    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    protected $helper;

    /**
     * @var \Dibs\EasyCheckout\Logger\Logger
     */
    protected $logger;

    /** @var \Dibs\EasyCheckout\Model\Dibs\Order $dibsOrderHandler */
    protected $dibsOrderHandler;

    /** @var \Magento\Sales\Api\OrderCustomerManagementInterface */
    protected $orderCustomerManagement;

    /** @var \Magento\Newsletter\Model\Subscriber $Subscriber */
    protected $subscriber;

    /** @var \Dibs\EasyCheckout\Model\Dibs\Locale $dibsLocale */
    protected $dibsLocale;

   /**
     * Constructor
     *
     * @param \Dibs\EasyCheckout\Helper\Data $helper
     * @param \Dibs\EasyCheckout\Model\Dibs\Order $dibsOrderHandler
     * @param \Dibs\EasyCheckout\Logger\Logger $logger
     * @param \Dibs\EasyCheckout\Model\Dibs\Locale $dibsLocale,
     * @param \Magento\Sales\Api\OrderCustomerManagementInterface $orderCustomerManagement
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     *
     */
    public function __construct(
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Dibs\EasyCheckout\Model\Dibs\Order $dibsOrderHandler,
        \Dibs\EasyCheckout\Logger\Logger $logger,
        \Dibs\EasyCheckout\Model\Dibs\Locale $dibsLocale,
        \Magento\Sales\Api\OrderCustomerManagementInterface $orderCustomerManagement,
        \Magento\Newsletter\Model\Subscriber $subscriber
    ) {
        $this->helper        = $helper;
        $this->logger = $logger;
        $this->dibsOrderHandler = $dibsOrderHandler;
        $this->dibsLocale = $dibsLocale;
        $this->orderCustomerManagement = $orderCustomerManagement;
        $this->subscriber = $subscriber;
    }

    /**
     * @return \Dibs\EasyCheckout\Helper\Data
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * @return \Dibs\EasyCheckout\Logger\Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

   /** @return \Dibs\EasyCheckout\Model\Dibs\Order */
    public function getDibsOrderHandler()
    {
        return $this->dibsOrderHandler;
    }

    /**
     * @return \Magento\Sales\Api\OrderCustomerManagementInterface
     */
    public function getOrderCustomerManagement()
    {
        return $this->orderCustomerManagement;
    }

    /**
     * @return \Magento\Newsletter\Model\Subscriber
     */
    public function getSubscriber()
    {
        return $this->subscriber;
    }

    /**
     * @return Dibs\Locale
     */
    public function getDibsLocale()
    {
        return $this->dibsLocale;
    }




}
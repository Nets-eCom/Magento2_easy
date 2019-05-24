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

   /**
     * Constructor
     *
     * @param \Dibs\EasyCheckout\Helper\Data $helper
     * @param \Dibs\EasyCheckout\Model\Dibs\Order $dibsOrderHandler
     * @param \Dibs\EasyCheckout\Logger\Logger $logger
     *
     */
    public function __construct(
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Dibs\EasyCheckout\Model\Dibs\Order $dibsOrderHandler,
        \Dibs\EasyCheckout\Logger\Logger $logger
    ) {
        $this->helper        = $helper;
        $this->logger = $logger;
        $this->dibsOrderHandler = $dibsOrderHandler;
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

}
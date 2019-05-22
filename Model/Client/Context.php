<?php

namespace Dibs\EasyCheckout\Model\Client;


class Context 
{
    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    protected $helper;

    /**
     * @var\Dibs\EasyCheckout\Logger
     */
    protected $logger;


   /**
     * Constructor
     *
     * @param \Dibs\EasyCheckout\Helper\Data $helper
     * @param \Dibs\EasyCheckout\Logger\Logger $logger
     *
     */
    public function __construct(
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Dibs\EasyCheckout\Logger\Logger $logger
    ) {
        $this->helper        = $helper;
        $this->logger = $logger;

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
    
}
<?php

namespace NexiCheckout\MagentoNexiCheckout\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Class Request
 */
class NexiCheckoutLog extends Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = 'var/log/nexi_checkout.log';
}

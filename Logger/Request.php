<?php

namespace Nexi\Checkout\Logger;

use Monolog\Logger;

/**
 * Class Request
 */
class Request extends \Magento\Framework\Logger\Handler\Base
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
    protected $fileName = 'var/log/nexi_payment_service_request.log';
}

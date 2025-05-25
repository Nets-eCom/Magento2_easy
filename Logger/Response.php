<?php

namespace Nexi\Checkout\Logger;

use Monolog\Logger;

/**
 * Class Response
 */
class Response extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::NOTICE;

    /**
     * File name
     * @var string
     */
    protected $fileName = 'var/log/nexi_payment_service_response.log';
}

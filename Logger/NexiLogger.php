<?php
declare(strict_types=1);

namespace Nexi\Checkout\Logger;

use Magento\Framework\Serialize\SerializerInterface;
use Nexi\Checkout\Gateway\Config\Config;

class NexiLogger extends \Magento\Framework\Logger\Monolog
{
    /**
     * @var array
     */
    private $debugActive = [];

    /**
     * NexiLogger constructor.
     *
     * @param string $name
     * @param SerializerInterface $serializer
     * @param Config $gatewayConfig
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        $name,
        private SerializerInterface $serializer,
        private Config $gatewayConfig,
        array $handlers = [],
        array $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Log data.
     *
     * @param int $level
     * @param string $message
     */
    public function logData($level, $message)
    {
        if ($message instanceof \Throwable) {
            $message = $message->getMessage();
        }

        if (is_array($message) || is_object($message)) {
            $message = $this->serializer->serialize($message);
        }

        $this->log(
            $level,
            $message
        );
    }

    /**
     * Debug log.
     *
     * @param string $type
     * @param string $message
     */
    public function debugLog($type, $message)
    {
        if (!$this->isDebugActive($type)) {
            return;
        }

        $level = $this->resolveLogLevel($type);
        $this->logData($level, $message);
    }

    /**
     * Resolve log level.
     *
     * @param string $logType
     * @return string
     */
    public function resolveLogLevel(string $logType) : string
    {
        $level = \Monolog\Logger::DEBUG;

        if ($logType == 'request') {
            $level = \Monolog\Logger::INFO;
        } elseif ($logType == 'response') {
            $level = \Monolog\Logger::NOTICE;
        }

        return $level;
    }

    /**
     * Is debug active.
     *
     * @param string $type
     * @return int
     */
    private function isDebugActive($type)
    {
        if (!isset($this->debugActive[$type])) {
            $this->debugActive[$type] = $type == 'request'
                ? $this->gatewayConfig->getRequestLog()
                : $this->gatewayConfig->getResponseLog();
        }

        return $this->debugActive[$type];
    }

    /**
     * Log data to file.
     *
     * @param string $logType
     * @param string $level
     * @param mixed $data
     * @return void
     */
    public function logCheckoutData($logType, $level, $data): void
    {
        if ($level !== 'error' &&
            (($logType === 'request' && $this->gatewayConfig->getRequestLog() == false)
                || ($logType === 'response' && $this->gatewayConfig->getResponseLog() == false))
        ) {
            return;
        }

        $level = $level == 'error' ? $level : $this->resolveLogLevel($logType);
        $this->logData($level, $data);
    }
}

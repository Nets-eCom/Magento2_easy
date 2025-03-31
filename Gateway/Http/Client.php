<?php

namespace Nexi\Checkout\Gateway\Http;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Nexi\Checkout\Gateway\Config\Config;
use NexiCheckout\Api\Exception\PaymentApiException;
use NexiCheckout\Api\PaymentApi;
use NexiCheckout\Factory\PaymentApiFactory;
use NexiCheckout\Model\Shared\JsonDeserializeInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;
use function PHPUnit\Framework\isNull;

class Client implements ClientInterface
{
    /**
     * @param PaymentApiFactory $paymentApiFactory
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly PaymentApiFactory $paymentApiFactory,
        private readonly Config            $config,
        private readonly LoggerInterface   $logger
    ) {
    }

    /***
     * Place request
     *
     * @param TransferInterface $transferObject
     *
     * @return array
     * @throws LocalizedException
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        try {
            $paymentApi = $this->getPaymentApi();
            $nexiMethod = $transferObject->getUri();
            $this->logRequest($nexiMethod, $transferObject);
            if (is_array($transferObject->getBody())) {
                $response = $paymentApi->$nexiMethod(...$transferObject->getBody());
            } else {
                $response = $paymentApi->$nexiMethod($transferObject->getBody());
            }
            $this->logResponse($response);
        } catch (PaymentApiException|\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new LocalizedException(__('An error occurred during the payment process. Please try again later.'));
        }

        return [$response];
    }

    /**
     * Get response data
     *
     * @param JsonDeserializeInterface $response
     *
     * @return string|false
     * @throws ReflectionException
     */
    public function getResponseData(JsonDeserializeInterface $response): string|false
    {
        $responseData = [];

        $responseReflection = new \ReflectionClass($response);
        $methods            = $responseReflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), 'get')) {
                $name                = $method->getName();
                $value               = $method->invoke($response);
                $responseData[$name] = $value;
            }
        }
        return json_encode($responseData);
    }

    /**
     * Get Payment API Client
     *
     * @return PaymentApi
     */
    public function getPaymentApi(): PaymentApi
    {
        return $this->paymentApiFactory->create(
            (string)$this->config->getApiKey(),
            $this->config->isLiveMode()
        );
    }

    /**
     * Log response
     *
     * @param ?JsonDeserializeInterface $response
     *
     * @return void
     * @throws ReflectionException
     */
    public function logResponse(?JsonDeserializeInterface $response): void
    {
        if ($response instanceof JsonDeserializeInterface) {
            $this->logger->debug(
                'Nexi response: ' . $this->getResponseData($response)
            );
        }
    }

    /**
     * Log request
     *
     * @param string $nexiMethod
     * @param TransferInterface $transferObject
     *
     * @return void
     */
    public function logRequest(string $nexiMethod, TransferInterface $transferObject): void
    {
        $this->logger->debug(
            'Nexi method: ' . $nexiMethod . PHP_EOL .
            'Nexi request: ' . json_encode($transferObject->getBody())
        );
    }
}

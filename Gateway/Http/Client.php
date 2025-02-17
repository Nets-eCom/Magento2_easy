<?php

namespace Nexi\Checkout\Gateway\Http;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Nexi\Checkout\Gateway\Config\Config;
use NexiCheckout\Api\Exception\PaymentApiException;
use NexiCheckout\Factory\PaymentApiFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Client
 */
class Client implements ClientInterface
{
    public function __construct(
        private readonly PaymentApiFactory $paymentApiFactory,
        private readonly Config            $config,
        private readonly LoggerInterface   $logger
    ) {
    }

    /**
     * @param TransferInterface $transferObject
     *
     * @return array
     * @throws PaymentApiException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = [];
        try {
            $paymentApi = $this->paymentApiFactory->create(
                (string)$this->config->getApiKey(),
                $this->config->isLiveMode()
            );
            $nexiMethod = $transferObject->getUri();
            $this->logger->debug(
                'Nexi method: ' . $nexiMethod . PHP_EOL .
                'Nexi request: ' . json_encode($transferObject->getBody())
            );
            $response = $paymentApi->$nexiMethod($transferObject->getBody());

            $this->logger->debug(
                'Nexi response: ' . $this->getResponseData($response)
            );
        } catch (PaymentApiException $e) {
            $this->logger->error($e->getMessage());
            throw new LocalizedException(__('An error occurred during the payment process. Please try again later.'));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new LocalizedException(__('An error occurred during the payment process. Please try again later.'));
        }

        return [$response];
    }

    /**
     * @param $response
     *
     * @return false|string
     */
    public function getResponseData($response): string|false
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
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Http;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Nexi\Checkout\Gateway\Config\Config;
use NexiCheckout\Api\Exception\PaymentApiException;
use NexiCheckout\Api\PaymentApi;
use NexiCheckout\Factory\PaymentApiFactory;
use Psr\Log\LoggerInterface;

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
        private readonly LoggerInterface   $logger,
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
            $this->logger->debug(
                'Nexi Client request: ',
                [
                    'method' => $nexiMethod,
                    'request' => $transferObject->getBody()
                ]
            );

            if (is_array($transferObject->getBody())) {
                $response = $paymentApi->$nexiMethod(...$transferObject->getBody());
            } else {
                $response = $paymentApi->$nexiMethod($transferObject->getBody());
            }

            $this->logger->debug(
                'Nexi Client response: ',
                ['response' => var_export($response, true)]
            );

        } catch (PaymentApiException|\Exception $e) {
            $this->logger->error($e->getMessage(), [$e]);
            throw new LocalizedException(__('An error occurred during the payment process. Please try again later.'));
        }

        return [$response];
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
}

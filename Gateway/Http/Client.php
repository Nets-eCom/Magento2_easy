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

            $response = $paymentApi->$nexiMethod($transferObject->getBody());
        } catch (PaymentApiException $e) {
            $this->logger->error($e->getMessage());
            throw new LocalizedException(__('An error occurred during the payment process. Please try again later.'));
        }

        return $response;
    }
}

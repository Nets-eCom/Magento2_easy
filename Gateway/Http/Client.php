<?php

namespace Nexi\Checkout\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Nexi\Checkout\Gateway\Config\Config;
use NexiCheckout\Api\Exception\PaymentApiException;
use NexiCheckout\Factory\PaymentApiFactory;
use NexiCheckout\Model\Request\Payment;

/**
 * Class Client
 */
class Client implements ClientInterface
{
    public function __construct(
        private readonly PaymentApiFactory $paymentApiFactory,
        private readonly Config            $config
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
        $paymentApi = $this->paymentApiFactory->create(
            (string)$this->config->getApiKey(),
            $this->config->isLiveMode()
        );

        $nexiMethod = $transferObject->getUri();


        $paymentApi->$nexiMethod($transferObject->getBody());

        return [];
    }
}

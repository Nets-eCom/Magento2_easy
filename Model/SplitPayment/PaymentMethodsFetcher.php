<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\SplitPayment;

use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Config\Source\PaymentTypesEnum;
use NexiCheckout\Api\Exception\PaymentApiException;
use NexiCheckout\Factory\PaymentApiFactory;
use NexiCheckout\Model\Request\PaymentMethods;
use Psr\Log\LoggerInterface;

class PaymentMethodsFetcher implements PaymentMethodsFetcherInterface
{
    public function __construct(
        private readonly PaymentApiFactory $paymentApiFactory,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getAvailablePaymentMethods(?string $currency = null): array
    {
        $secretKey = $this->config->getApiKey();
        $isLiveMode = $this->config->isLiveMode();
        
        if (empty($secretKey)) {
            $this->logger->warning('Nexi API key not configured, using fallback payment types');

            return [];
        }
        
        $paymentApi = $this->paymentApiFactory->create($secretKey, $isLiveMode);
            
        try {
            $result = $paymentApi->getPaymentMethods(new PaymentMethods(null, $currency, true));
        } catch (PaymentApiException $e) {
            $this->logger->error('Failed to fetch payment methods from Nexi API: ' . $e->getMessage());

            return [];
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error fetching payment methods: ' . $e->getMessage());

            return [];
        }

        $options = [
            [
                'value' => PaymentTypesEnum::CARD->value,
                'label' => __(PaymentTypesEnum::CARD->value)
            ]
        ];
        foreach ($result->getMethods() as $method) {
            if ($method->getPaymentType() === PaymentTypesEnum::CARD->value) {
                continue;
            }

            $options[] = [
                'value' => $method->getName(),
                'label' => __($method->getName())
            ];
        }
        
        // Remove duplicates based on value when payment method available in multiple currencies
        $options = array_values(array_reduce($options, function ($carry, $item) {
            $carry[$item['value']] = $item;
            return $carry;
        }, []));
        
        return $options;
    }
}

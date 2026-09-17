<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Nexi\Checkout\Model\SplitPayment\CachedPaymentMethodsFetcher;
use Nexi\Checkout\Model\SplitPayment\PaymentMethodsFetcherInterface;

class PayTypeOptions implements OptionSourceInterface
{
    public function __construct(
        private readonly PaymentMethodsFetcherInterface $paymentMethodsFetcher,
    ) {
    }

    /**
     * @return array{
     *  value: string,
     *  label: string,
     * }
     */
    public function toOptionArray(): array
    {
        if ($this->paymentMethodsFetcher instanceof CachedPaymentMethodsFetcher) {
            $this->paymentMethodsFetcher->clear();
        }

        $options = $this->paymentMethodsFetcher->getAvailablePaymentMethods();

        if (empty($options)) {
            return $this->getFallbackOptions();
        }

        return $options;
    }

    /**
     * @return array{
     *  value: string,
     *  label: string,
     * }
     */
    private function getFallbackOptions(): array
    {
        return [
            [
                'value' => PaymentTypesEnum::CARD->value,
                'label' => __(PaymentTypesEnum::CARD->value)
            ]
        ];
    }
}

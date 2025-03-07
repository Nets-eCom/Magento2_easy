<?php

namespace Nexi\Checkout\Model;

use Composer\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Nexi\Checkout\Gateway\Command\Initialize;
use Nexi\Checkout\Gateway\Request\CreatePaymentRequestBuilder;
use Nexi\Checkout\Api\PaymentInitializeInterface;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;

class PaymentInitialize implements PaymentInitializeInterface
{
    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param Initialize $initializeCommand
     * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
     */
    public function __construct(
        private readonly CartRepositoryInterface           $quoteRepository,
        private readonly Initialize                              $initializeCommand,
        private readonly PaymentDataObjectFactoryInterface       $paymentDataObjectFactory,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly \Nexi\Checkout\Gateway\Config\Config $config,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function initialize(string $cartId, string $integrationType, $paymentMethod): string
    {
        try {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

            $quote         = $this->quoteRepository->get($quoteIdMask->getQuoteId());
            $paymentMethod = $quote->getPayment();
            if (!$paymentMethod) {
                throw new LocalizedException(__('No payment method found for the quote'));
            }
            if ($paymentMethod->getAdditionalInformation('payment_id')) {

            } else {
                $paymentData  = $this->paymentDataObjectFactory->create($paymentMethod);
                $cratePayment = $this->initializeCommand->cratePayment($paymentData);
                $quote->setData('no_payment_update_flag', true);
                $this->quoteRepository->save($quote);
            }

            return match ($integrationType) {
                IntegrationTypeEnum::HostedPaymentPage->name => json_encode(
                    [
                        'redirect_url' => $cratePayment['body']['payment']['checkout']['url']
                    ]
                ),
                IntegrationTypeEnum::EmbeddedCheckout->name => json_encode(
                    [
                        'paymentId'   => $paymentMethod->getAdditionalInformation('payment_id'),
                        'checkoutKey' => $this->config->getCheckoutKey()
                    ]
                ),
                default => throw new LocalizedException(__('Invalid integration type'))
            };
        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not initialize payment: %1', $e->getMessage()));
        }
    }
}

<?php

namespace Nexi\Checkout\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Nexi\Checkout\Gateway\Command\Initialize;
use Nexi\Checkout\Api\PaymentInitializeInterface;
use Nexi\Checkout\Gateway\Config\Config;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;
use Psr\Log\LoggerInterface;

class PaymentInitialize implements PaymentInitializeInterface
{
    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param Initialize $initializeCommand
     * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param Config $config
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param ResolverInterface $localeResolver
     * @param Language $language
     */
    public function __construct(
        private readonly CartRepositoryInterface           $quoteRepository,
        private readonly Initialize                        $initializeCommand,
        private readonly PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        private readonly QuoteIdMaskFactory                $quoteIdMaskFactory,
        private readonly Config                            $config,
        private readonly LoggerInterface                   $logger,
        private readonly Session                           $checkoutSession,
        private readonly ResolverInterface                 $localeResolver,
        private readonly Language                          $language
    ) {
    }

    /**
     * @inheritDoc
     */
    public function initialize(string $cartId, string $integrationType, $paymentMethod): string
    {
        try {

            if (!is_numeric($cartId)) {
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
                $cartId = $quoteIdMask->getQuoteId();
            }
            $quote         = $this->quoteRepository->get($cartId);

            if (!$quote->getIsActive()) {
                $this->checkoutSession->restoreQuote();
            }
            $paymentMethod = $quote->getPayment();
            if (!$paymentMethod) {
                throw new LocalizedException(__('No payment method found for the quote'));
            }

            $paymentData  = $this->paymentDataObjectFactory->create($paymentMethod);
            $createPayment = $this->initializeCommand->createPayment($paymentData);
            $quote->setData('no_payment_update_flag', true);
            $this->quoteRepository->save($quote);

            $locale = $this->language->getCode();

            return match ($integrationType) {
                IntegrationTypeEnum::HostedPaymentPage->name => json_encode(
                    [
                        'redirect_url' => $createPayment['body']['payment']['checkout']['url'] . '&language=' . $locale
                    ]
                ),
                IntegrationTypeEnum::EmbeddedCheckout->name => json_encode(
                    [
                        'paymentId'   => $paymentMethod->getAdditionalInformation('payment_id'),
                        'checkoutKey' => $this->config->getCheckoutKey(),
                        'locale'      => $locale,
                    ]
                ),
                default => throw new InputException(__('Invalid integration type'))
            };
        } catch (\Exception $e) {
            $this->logger->error(
                'Error initializing payment:',
                [
                    'exception' => $e->getMessage(),
                    'trace'     => $e->getTraceAsString()
                ]
            );
            throw new LocalizedException(__('Could not initialize payment.'), $e);
        }
    }
}

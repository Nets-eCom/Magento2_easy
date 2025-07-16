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
     * List of supported locales for Nexi Checkout
     */
    private const SUPPORTED_LOCALES = [
        'en-GB', // English (default)
        'da-DK', // Danish
        'nl-NL', // Dutch
        'ee-EE', // Estonian
        'fi-FI', // Finnish
        'fr-FR', // French
        'de-DE', // German
        'it-IT', // Italian
        'lv-LV', // Latvian
        'lt-LT', // Lithuanian
        'nb-NO', // Norwegian
        'pl-PL', // Polish
        'es-ES', // Spanish
        'sk-SK', // Slovak
        'sv-SE', // Swedish
    ];

    /**
     * Default locale to use if the current locale is not supported
     */
    private const DEFAULT_LOCALE = 'en-GB';

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param Initialize $initializeCommand
     * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param Config $config
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        private readonly CartRepositoryInterface           $quoteRepository,
        private readonly Initialize                        $initializeCommand,
        private readonly PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        private readonly QuoteIdMaskFactory                $quoteIdMaskFactory,
        private readonly Config                            $config,
        private readonly LoggerInterface                   $logger,
        private readonly Session                           $checkoutSession,
        private readonly ResolverInterface                 $localeResolver
    ) {
    }

    /**
     * Validates if the given locale is supported by Nexi Checkout
     *
     * @param string $locale The locale to validate
     * @return string The validated locale or the default locale if not supported
     */
    private function validateLocale(string $locale): string
    {
        return in_array($locale, self::SUPPORTED_LOCALES) ? $locale : self::DEFAULT_LOCALE;
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

            return match ($integrationType) {
                IntegrationTypeEnum::HostedPaymentPage->name => json_encode(
                    [
                        'redirect_url' => $createPayment['body']['payment']['checkout']['url']
                    ]
                ),
                IntegrationTypeEnum::EmbeddedCheckout->name => json_encode(
                    [
                        'paymentId'   => $paymentMethod->getAdditionalInformation('payment_id'),
                        'checkoutKey' => $this->config->getCheckoutKey(),
                        'locale'      => $this->validateLocale(
                            str_replace('_', '-', $this->localeResolver->getLocale())
                        )
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

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Nexi\Checkout\Gateway\Command\Initialize;
use Nexi\Checkout\Api\PaymentInitializeInterface;
use Nexi\Checkout\Gateway\Config\Config;
use NexiCheckout\Model\Request\UpdateOrder\PaymentMethod;
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
     */
    public function __construct(
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly Initialize $initializeCommand,
        private readonly PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly Session $checkoutSession
    ) {
    }

    /**
     * @inheritDoc
     */
    public function initialize(string $cartId, string $integrationType, PaymentInterface $paymentMethod): string
    {
        try {
            if (!is_numeric($cartId)) {
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
                $cartId = $quoteIdMask->getQuoteId();
            }
            $quote = $this->quoteRepository->get($cartId);

            if (!$quote->getIsActive()) {
                $this->checkoutSession->restoreQuote();
            }
            $quotePayment = $quote->getPayment();
            if (!$quotePayment) {
                throw new LocalizedException(__('No payment method found for the quote'));
            }

            $paymentData = $this->paymentDataObjectFactory->create($quotePayment);

            if (isset($paymentMethod->getAdditionalData()['subselection'])) {
                $paymentData->getPayment()->setAdditionalInformation(
                    'subselection',
                    $paymentMethod->getAdditionalData()['subselection']
                );
            }

            $this->initializeCommand->createPayment($paymentData);
            $this->quoteRepository->save($quote);

            return json_encode([
                'paymentId'   => $quotePayment->getAdditionalInformation('payment_id'),
                'checkoutKey' => $this->config->getCheckoutKey()
            ]);
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

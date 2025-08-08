<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Nexi\Checkout\Api\PaymentValidateInterface;
use Nexi\Checkout\Gateway\AmountConverter;
use Nexi\Checkout\Gateway\Config\Config;
use NexiCheckout\Model\Result\RetrievePaymentResult;
use Psr\Log\LoggerInterface;

class PaymentValidate implements PaymentValidateInterface
{
    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param LoggerInterface $logger
     * @param Json $json
     * @param CommandManagerPoolInterface $commandManagerPool
     * @param AmountConverter $amountConverter
     * @param Session $session
     */
    public function __construct(
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly LoggerInterface $logger,
        private readonly Json $json,
        private readonly CommandManagerPoolInterface $commandManagerPool,
        private readonly AmountConverter $amountConverter,
        private readonly Session $session,
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function validate(string $cartId): string
    {
        try {
            if (!is_numeric($cartId)) {
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
                $cartId = $quoteIdMask->getQuoteId();
            }
            $quote = $this->quoteRepository->get($cartId);

            if (!$quote->getIsActive()) {
                $this->session->restoreQuote();
            }

            $paymentMethod = $quote->getPayment();
            if (!$paymentMethod) {
                throw new LocalizedException(__('No payment method found for the quote'));
            }

            $paymentId = $paymentMethod->getAdditionalInformation('payment_id');

            $this->commandManagerPool->get(Config::CODE)->executeByCode(
                'retrieve',
                $paymentMethod,
                [
                    'quote' => $quote
                ]
            );

            $this->compareAmounts($paymentMethod->getData('retrieved_payment'), $quote);

            return $this->json->serialize([
                'payment_id' => $paymentId,
                'success'    => true
            ]);

        } catch (\Exception $e) {
            $this->logger->error(
                'Error initializing payment:',
                [
                    'exception' => $e->getMessage(),
                    'trace'     => $e->getTraceAsString()
                ]
            );
            throw new LocalizedException(__('Could not finalize payment.'), $e);
        }
    }

    /**
     * Compare items and amounts between retrieved payment and quote
     *
     * @param RetrievePaymentResult $retrievedPayment
     * @param CartInterface $quote
     *
     * @return void
     * @throws LocalizedException
     */
    private function compareAmounts(RetrievePaymentResult $retrievedPayment, CartInterface $quote): void
    {
        $quoteTotal = $this->amountConverter->convertToNexiAmount($quote->getGrandTotal());
        $retrievedTotal = $retrievedPayment->getPayment()->getOrderDetails()->getAmount();

        if ($quoteTotal !== $retrievedTotal) {
            throw new LocalizedException(__('The payment amount does not match the quote total.'));
        }
    }
}

<?php

namespace Nexi\Checkout\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Nexi\Checkout\Api\PaymentValidateInterface;
use Nexi\Checkout\Gateway\Config\Config;
use NexiCheckout\Model\Result\RetrievePaymentResult;
use Psr\Log\LoggerInterface;

class PaymentValidate implements PaymentValidateInterface
{
    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param LoggerInterface $logger
     * @param Json $json
     * @param CommandManagerPoolInterface $commandManagerPool
     */
    public function __construct(
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly LoggerInterface $logger,
        private readonly Json $json,
        private readonly CommandManagerPoolInterface $commandManagerPool,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function validate(string $cartId): string
    {
        try {
            if (!is_numeric($cartId)) {
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
                $cartId      = $quoteIdMask->getQuoteId();
            }
            $quote         = $this->quoteRepository->get($cartId);
            $paymentMethod = $quote->getPayment();
            if (!$paymentMethod) {
                throw new LocalizedException(__('No payment method found for the quote'));
            }

            $paymentData = $this->paymentDataObjectFactory->create($paymentMethod);

            $paymentId = $paymentMethod->getAdditionalInformation('payment_id');

            $paymentDeteaild = $this->commandManagerPool->get(Config::CODE)->executeByCode(
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
     * @param array $retrievedPayment
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return void
     * @throws LocalizedException
     */
    private function compareAmounts(RetrievePaymentResult $retrievedPayment, \Magento\Quote\Model\Quote $quote): void
    {
        $quoteTotal     = $quote->getGrandTotal() * 100;
        $retrievedTotal = $retrievedPayment->getPayment()->getOrderDetails()->getAmount();

        if ((float)$quoteTotal !== (float)$retrievedTotal) {
            throw new LocalizedException(__('The payment amount does not match the quote total.'));
        }
    }
}

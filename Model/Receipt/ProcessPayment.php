<?php

namespace Nexi\Checkout\Model\Receipt;

use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Gateway\Validator\HmacValidator;
use Nexi\Checkout\Gateway\Validator\ResponseValidator;
use Nexi\Checkout\Exceptions\CheckoutException;
use Nexi\Checkout\Model\FinnishReferenceNumber;
use Nexi\Checkout\Model\ReceiptDataProvider;
use Nexi\Checkout\Exceptions\TransactionSuccessException;

class ProcessPayment
{
    private const PAYMENT_PROCESSING_CACHE_PREFIX = "nexi-processing-payment-";

    /**
     * ProcessPayment constructor.
     *
     * @param ResponseValidator $responseValidator
     * @param ReceiptDataProvider $receiptDataProvider
     * @param CartRepositoryInterface $cartRepository
     * @param Config $gatewayConfig
     * @param FinnishReferenceNumber $finnishReferenceNumber
     */
    public function __construct(
        private ResponseValidator       $responseValidator,
        private ReceiptDataProvider     $receiptDataProvider,
        private CartRepositoryInterface $cartRepository,
        private Config                  $gatewayConfig,
        private FinnishReferenceNumber  $finnishReferenceNumber
    ) {
    }

    /**
     * Process function
     *
     * @param array $params
     * @param Session $session
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($params, $session)
    {
        /** @var array $errors */
        $errors = [];

        /** @var \Magento\Payment\Gateway\Validator\Result $validationResponse */
        $validationResponse = $this->responseValidator->validate($params);

        if (!$validationResponse->isValid()) { // if response params are not valid, redirect back to the cart

            /** @var string $failMessage */
            foreach ($validationResponse->getFailsDescription() as $failMessage) {
                $errors[] = $failMessage;
            }

            $session->restoreQuote(); // should it be restored?

            return $errors;
        }

        /** @var string $reference */
        $reference = $params['checkout-reference'];

        /** @var string $orderNo */
        $orderNo = $this->gatewayConfig->getGenerateReferenceForOrder()
            ? $this->finnishReferenceNumber->getIdFromOrderReferenceNumber($reference)
            : $reference;

        /** @var array $ret */
        $ret = $this->processPayment($params, $session, $orderNo);

        return array_merge($ret, $errors);
    }

    /**
     * ProcessPayment function
     *
     * @param array $params
     * @param Session $session
     * @param string $orderNo
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processPayment($params, $session, $orderNo)
    {
        /** @var array $errors */
        $errors = [];

        /** @var bool $isValid */
        $isValid = true;

        /** @var null|string $failMessage */
        $failMessage = null;

        if (empty($orderNo)) {
            $session->restoreQuote();

            return $errors;
        }

        try {
            /*
            there are 2 calls called from Nexi Payment Service.
            One call is when a customer is redirected back to the magento store.
            There is also the second, parallel, call from Nexi Payment Service
            to make sure the payment is confirmed (if for any reason customer was not redirected back to the store).
            Sometimes, the calls are called with too small time difference between them that Magento cannot handle them.
            The second call must be ignored or slowed down.
            */
            $this->receiptDataProvider->execute($params);
        } catch (CheckoutException $exception) {
            $isValid = false;
            $failMessage = $exception->getMessage();
            array_push($errors, $failMessage);
        } catch (TransactionSuccessException $successException) {
            $isValid = true;
        }

        if ($isValid == false) {
            $session->restoreQuote();
        } else {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $session->getQuote();
            $quote->setIsActive(false);
            $this->cartRepository->save($quote);
        }

        return $errors;
    }
}

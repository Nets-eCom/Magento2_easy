<?php

namespace Nexi\Checkout\Model\Receipt;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface as TransactionBuilderInterface;
use Nexi\Checkout\Exceptions\CheckoutException;
use Nexi\Checkout\Gateway\Validator\HmacValidator;
use Nexi\Checkout\Logger\NexiLogger;

class PaymentTransaction
{
    /**
     * PaymentTransaction constructor.
     *
     * @param TransactionBuilderInterface $transactionBuilder
     * @param HmacValidator $hmacValidator
     * @param CancelOrderService $cancelOrderService
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param NexiLogger $nexiLogger
     */
    public function __construct(
        private TransactionBuilderInterface $transactionBuilder,
        private HmacValidator               $hmacValidator,
        private CancelOrderService          $cancelOrderService,
        private OrderRepositoryInterface    $orderRepositoryInterface,
        private NexiLogger              $nexiLogger
    ) {
    }

    /**
     * AddPaymentTransaction function
     *
     * @param Order $order
     * @param string $transactionId
     * @param array $details
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function addPaymentTransaction(Order $order, $transactionId, array $details = [])
    {
        /** @var \Magento\Framework\DataObject|\Magento\Sales\Api\Data\OrderPaymentInterface |mixed|null $payment */
        $payment = $order->getPayment();

        /** @var \Magento\Sales\Api\Data\TransactionInterface $transaction */
        $transaction = $this->transactionBuilder
            ->setPayment($payment)->setOrder($order)
            ->setTransactionId($transactionId)
            ->setAdditionalInformation([Transaction::RAW_DETAILS => (array)$details])
            ->setFailSafe(true)
            ->build(Transaction::TYPE_CAPTURE);
        $transaction->setIsClosed(0);
        return $transaction;
    }

    /**
     * VerifyPaymentData function
     *
     * @param array $params
     * @param Order $currentOrder
     * @return mixed|string|void
     * @throws \Nexi\Checkout\Exceptions\CheckoutException
     */
    public function verifyPaymentData($params, $currentOrder)
    {
        $status = $params['checkout-status'];

        // skip HMAC validator if signature is 'skip_hmac' for token payment
        if ($params['signature'] === HmacValidator::SKIP_HMAC_VALIDATION) {
            $verifiedPayment = true;
        } else {
            $verifiedPayment = $this->hmacValidator->validateHmac($params, $params['signature']);
        }

        if ($verifiedPayment && ($status === 'ok' || $status == 'pending' || $status == 'delayed')) {
            return $status;
        } else {
            $currentOrder->addCommentToStatusHistory(__('Failed to complete the payment.'));
            $this->orderRepositoryInterface->save($currentOrder);
            $this->cancelOrderService->cancelOrderById($currentOrder->getId());

            $this->nexiLogger->logData(
                \Monolog\Logger::ERROR,
                'Failed to complete the payment. Please try again or contact the customer service.'
            );
            throw new CheckoutException(
                __('Failed to complete the payment. Please try again or contact the customer service.')
            );
        }
    }
}

<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Token;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandManagerPool;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Nexi\Checkout\Gateway\Request\TokenRequestDataBuilder;
use Nexi\Checkout\Logger\NexiLogger;
use Nexi\Checkout\Model\Adapter\Adapter;
use Nexi\Checkout\Model\Subscription;
use Nexi\Checkout\Model\Token\RequestData;
// TODO: Use Nexi sdk
//use Paytrail\SDK\Request\MitPaymentRequest;
//use Paytrail\SDK\Response\MitPaymentResponse;

class Payment
{
    /**
     * Payment constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param Adapter $adapter
     * @param RequestData $requestData
     * @param CustomerRepositoryInterface $customerRepository
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param BuilderInterface $transactionBuilder
     * @param OrderManagementInterface $orderManagement
     * @param OrderStatusHistoryInterfaceFactory $orderStatusHistoryFactory
     * @param OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     * @param NexiLogger $paytrailLogger
     * @param TokenRequestDataBuilder $tokenRequestDataBuilder
     * @param CommandManagerPool $commandManagerPool
     */
    public function __construct(
        private OrderRepositoryInterface              $orderRepository,
        private Adapter                               $adapter,
        private RequestData                           $requestData,
        private CustomerRepositoryInterface           $customerRepository,
        private InvoiceService                        $invoiceService,
        private Transaction                           $transaction,
        private BuilderInterface                      $transactionBuilder,
        private OrderManagementInterface              $orderManagement,
        private OrderStatusHistoryInterfaceFactory    $orderStatusHistoryFactory,
        private OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository,
        private NexiLogger                        $paytrailLogger,
        private TokenRequestDataBuilder               $tokenRequestDataBuilder,
        private CommandManagerPool                    $commandManagerPool
    ) {
    }

    /**
     * Make MIT payment request.
     *
     * @see https://checkoutfinland.github.io/psp-api/#/?id=merchant-initiated-transactions-mit
     *
     * @param int $orderId
     * @param string $cardToken
     *
     * @return boolean
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function makeMitPayment($orderId, $cardToken)
    {
        try {
            $order           = $this->orderRepository->get($orderId);
            $customer        = $this->customerRepository->getById((int)$order->getCustomerId());
            $commandExecutor = $this->commandManagerPool->get('paytrail');

            $mitResponse = $commandExecutor->executeByCode(
                'token_payment_mit',
                null,
                [
                    'order'    => $order,
                    'token_id' => $cardToken,
                    'customer' => $customer,
                ]
            );

            if ($mitResponse['data']?->getTransactionId() === null) {
                $this->paytrailLogger->logCheckoutData(
                    'response',
                    'error',
                    'A problem occurred: Payment transaction id missing in request response'
                );

                return false;
            }
        } catch (\Exception $e) {
            $this->paytrailLogger->logCheckoutData(
                'response',
                'error',
                'A problem occurred: ' . $e->getMessage()
            );
            return false;
        }//end try

        $this->createInvoice($order, $mitResponse['data']);
        $this->createTransaction($order, $mitResponse['data']);
        $this->updateOrder($order, $mitResponse['data']);

        return true;
    }

    /**
     * Create invoice.
     *
     * @param OrderInterface $order
     * @param MitPaymentResponse $mitResponse
     */
    private function createInvoice($order, $mitResponse)
    {
        if ($order->canInvoice()) {
            try {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->setTransactionId($mitResponse->getTransactionId());
                $invoice->save();
                $transactionSave = $this->transaction->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionSave->save();
            } catch (\Exception $e) {
                $this->paytrailLogger->logCheckoutData(
                    'response',
                    'error',
                    'A problem with creating invoice after payment '
                    . $e->getMessage()
                );
            }
        }
    }

    /**
     * Create transaction.
     *
     * @param OrderInterface $order
     * @param MitPaymentResponse $mitResponse
     *
     * @return int|void
     */
    private function createTransaction($order, $mitResponse)
    {
        try {
            $payment = $order->getPayment();
            $payment->setLastTransId($mitResponse->getTransactionId());
            $payment->setTransactionId($mitResponse->getTransactionId());
            $payment->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$mitResponse]
            );

            $trans       = $this->transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($mitResponse->getTransactionId())
                ->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$mitResponse]
                )
                ->setFailSafe(true)
                //build method creates the transaction and returns the object
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();

            return $transaction->save()->getTransactionId();
        } catch (\Exception $e) {
            $this->paytrailLogger->logCheckoutData(
                'response',
                'error',
                'A problem occurred: while creating transaction'
                . $e->getMessage()
            );
        }
    }

    /**
     * Get MIT payment request.
     *
     * @return MitPaymentRequest
     */
    private function getMitPaymentRequest(): MitPaymentRequest
    {
        return new MitPaymentRequest();
    }

    /**
     * Update order.
     *
     * @param OrderInterface $order
     * @param MitPaymentResponse $mitResponse
     *
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function updateOrder(
        OrderInterface     $order,
        MitPaymentResponse $mitResponse
    ): void {

        $commentsArray = [
            'pending_payment' => __('Transaction ID: ') . $mitResponse->getTransactionId(),
            'processing'      => __('Payment has been completed')
        ];

        foreach ($commentsArray as $status => $comment) {
            $historyComment = $this->orderStatusHistoryFactory->create();
            $historyComment
                ->setStatus($status)
                ->setComment($comment);
            $this->orderManagement->addComment($order->getEntityId(), $historyComment);
            $this->orderStatusHistoryRepository->save($historyComment);
        }

        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $this->orderRepository->save($order);
    }
}

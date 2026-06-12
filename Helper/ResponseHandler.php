<?php

declare(strict_types=1);

namespace Dibs\EasyCheckout\Helper;

use Dibs\EasyCheckout\Logger\Logger;
use Dibs\EasyCheckout\Model\Client\DTO\GetPaymentResponse;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;

class ResponseHandler
{
    private OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository;
    private InvoiceService $invoiceService;
    private Transaction $transaction;
    private InvoiceSender $invoiceSender;
    private Data $dibsDataHelper;
    private Logger $logger;
    public function __construct(
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository,
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender,
        Data $dibsDataHelper,
        Logger $logger
    ) {
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->dibsDataHelper = $dibsDataHelper;
        $this->logger = $logger;
    }

    /**
     *
     * @param GetPaymentResponse $paymentResponse
     *
     * @return bool
     */
    public function isOrderValid(GetPaymentResponse $paymentResponse)
    {
        return $paymentResponse->getSummary()->getChargedAmount() == $paymentResponse->getOrderDetails()->getAmount();
    }

    /**
     * Payment is already made, so we need to handle order status
     *
     * @param GetPaymentResponse $paymentResponse
     * @param Order $order
     */
    public function saveOrder(
        GetPaymentResponse $paymentResponse,
        Order $order
    ) {
        if (!$this->isOrderValid($paymentResponse)) {
            return;
        }
        $this->invoiceOrder($order);
    }

    /**
     * @param Order $order
     */
    private function invoiceOrder(Order $order) : void
    {
        if (!$order->canInvoice()) {
            return;
        }

        if (!$this->dibsDataHelper->getCharge()) {
            return;
        }
        try {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
            $this->invoiceSender->send($invoice);

            if ($invoice->canCapture()) {
                $invoice->capture();
            }
            //send notification code
            $statusHistory = $order->addCommentToStatusHistory(
                __('Notified customer about invoice #%1.', $invoice->getId())
            )->setIsCustomerNotified(true);
            $this->orderStatusHistoryRepository->save($statusHistory);

        } catch (\Exception $e) {
            $message = sprintf('There was an issue with invoicing the order: %s', $e->getMessage());

            $this->logger->error($message, ['exception' => $e]);

            $statusHistory = $order->addCommentToStatusHistory($message)->setIsCustomerNotified(false);
            $this->orderStatusHistoryRepository->save($statusHistory);

            throw $e;
        }
    }
}

<?php

declare(strict_types=1);

namespace Dibs\EasyCheckout\Helper;

use Dibs\EasyCheckout\Logger\Logger;
use Dibs\EasyCheckout\Model\Client\DTO\GetPaymentResponse;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;

class ResponseHandler
{
    private OrderRepositoryInterface $orderRepository;
    private InvoiceService $invoiceService;
    private Transaction $transaction;
    private InvoiceSender $invoiceSender;
    private Data $dibsDataHelper;
    private Logger $logger;
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        Transaction $transaction,
        InvoiceSender $invoiceSender,
        Data $dibsDataHelper,
        Logger $logger
    ) {
        $this->orderRepository = $orderRepository;
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

            $invoice->capture();
            //send notification code
            $order->addCommentToStatusHistory(
                __('Notified customer about invoice #%1.', $invoice->getId())
            )->setIsCustomerNotified(true);
            $this->orderRepository->save($order);

        } catch (\Exception $e) {
            $message = sprintf('There was an issue with invoicing the order: %s', $e->getMessage());

            $this->logger->error($message, ['exception' => $e]);

            $order->addCommentToStatusHistory($message)->setIsCustomerNotified(false);
            $this->orderRepository->save($order);
        }
    }
}

<?php declare(strict_types=1);

namespace Dibs\EasyCheckout\Helper;

use Dibs\EasyCheckout\Model\Client\DTO\GetPaymentResponse;
use Magento\Sales\Model\Order;

class SwishResponseHandler
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    private $invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    private $transaction;

    /**
     * @var Order\Email\Sender\InvoiceSender
     */
    private $invoiceSender;

    /**
     * SwishResponseHandler constructor.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
    ) {
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
    }

    /**
     *
     * @param GetPaymentResponse $paymentResponse
     *
     * @return bool
     */
    public function isSwishOrderValid(GetPaymentResponse $paymentResponse)
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
        if (!$this->isSwishOrderValid($paymentResponse)) {
            return;
        }
        $this->invoiceOrder($order);
        $paymentMethod = $paymentResponse->getPaymentDetails()->getPaymentMethod();
        if (strtoupper($paymentMethod) == 'SWISH') {
            $order
                ->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                ->addCommentToStatusHistory('Swish Payment Completed', true, true)
            ;
            $this->orderRepository->save($order);
        }
    }

    /**
     * @param Order $order
     */
    private function invoiceOrder(Order $order) : void
    {
        if (!$order->canInvoice()) {
            return;
        }

        try {
            $invoice = $this->invoiceService->prepareInvoice($order);
	    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
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
            $order->setIsInProcess(true);
            //send notification code
            $order->addCommentToStatusHistory(
                __('Notified customer about invoice #%1.', $invoice->getId())
	    )->setIsCustomerNotified(true)
	    ->save();
        } catch (\Exception $e) {
            // We cannot brake transaction
        }
    }
}
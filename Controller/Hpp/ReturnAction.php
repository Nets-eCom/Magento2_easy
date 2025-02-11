<?php

namespace Nexi\Checkout\Controller\Hpp;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\OrderRepository;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Transaction\Builder;

class ReturnAction implements ActionInterface
{
    public function __construct(
        private readonly RedirectFactory  $resultRedirectFactory,
        private readonly RequestInterface $request,
        private readonly UrlInterface     $url,
        private readonly Session          $checkoutSession,
        private readonly Config           $config,
        private readonly Builder          $transactionBuilder,
        private readonly OrderRepository  $orderRepository,
    ) {
    }

    public function execute(): ResultInterface
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if ($order->getPayment()->getAdditionalInformation('payment_id') != $this->request->getParam('paymentid')) {
            throw new LocalizedException(__('Payment ID does not match.'));
        }

        if ($this->config->getPaymentAction() == MethodInterface::ACTION_AUTHORIZE) {
            // create pending invoice and set order status to pending
            $order->setState(Order::STATE_PENDING_PAYMENT)
                ->setStatus(Order::STATE_PENDING_PAYMENT);
            $paymentTransaction = $this->transactionBuilder->build(
                $order,
                ['payment_id' => $this->request->getParam('paymentid')],
                Transaction::TYPE_AUTH
            );

            $order->addRelatedObject($paymentTransaction);
        } elseif ($this->config->getPaymentAction() == MethodInterface::ACTION_AUTHORIZE_CAPTURE) {
            $order->setState(Order::STATE_PROCESSING)
                ->setStatus(Order::STATE_PROCESSING);
            $paymentTransaction = $this->transactionBuilder->build(
                $order,
                ['payment_id' => $this->request->getParam('paymentid')],
                Transaction::TYPE_CAPTURE
            );

            $invoice = $order->prepareInvoice();
            $invoice->register();
            $invoice->setTransactionId($paymentTransaction->getTxnId());
            $invoice->pay();

            $order->addRelatedObject($invoice);
            $order->addRelatedObject($paymentTransaction);
        }

        $this->orderRepository->save($order);

        return $this->resultRedirectFactory->create()->setUrl(
            $this->url->getUrl('checkout/onepage/success', ['_secure' => true])
        );
    }
}

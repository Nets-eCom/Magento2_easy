<?php

namespace Nexi\Checkout\Controller\Hpp;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\OrderRepository;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Transaction\Builder;
use Psr\Log\LoggerInterface;

class ReturnAction implements ActionInterface
{
    public function __construct(
        private RedirectFactory  $resultRedirectFactory,
        private RequestInterface $request,
        private UrlInterface     $url,
        private Session          $checkoutSession,
        private Config           $config,
        private Builder          $transactionBuilder,
        private OrderRepository  $orderRepository,
        private LoggerInterface  $logger,
        private ManagerInterface $messageManager
    ) {
    }

    public function execute(): ResultInterface
    {
        $order = $this->checkoutSession->getLastRealOrder();

        $this->logger->debug(
            'ReturnAction request: ' . json_encode($this->request->getParams())
            . ' - Order ID: ' . $order->getIncrementId()
            . 'http referrer: ' . $this->request->getServer('HTTP_REFERER')
        );

        try {
            if ($order->getPayment()->getAdditionalInformation('payment_id') != $this->request->getParam('paymentid')) {
                throw new LocalizedException(__('Payment ID does not match.'));
            }

            if ($order->getState() != Order::STATE_NEW) {
                $this->messageManager->addNoticeMessage(__('Payment already processed'));
                throw new LocalizedException(__('Payment already processed'));
            }

            $paymentAction = $this->config->getPaymentAction();
            $paymentId     = $this->request->getParam('paymentid');

            if ($paymentAction == MethodInterface::ACTION_AUTHORIZE) {
                $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
                $paymentTransaction = $this->transactionBuilder->build(
                    $order,
                    ['payment_id' => $paymentId],
                    Transaction::TYPE_AUTH
                );
                $order->addRelatedObject($paymentTransaction);
            } elseif ($paymentAction == MethodInterface::ACTION_AUTHORIZE_CAPTURE) {
                $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                $paymentTransaction = $this->transactionBuilder->build(
                    $order,
                    ['payment_id' => $paymentId],
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
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . ' - ' . $e->getTraceAsString());
            $this->messageManager->addErrorMessage(
                __('An error occurred during the payment process. Please try again later.')
            );

            return $this->resultRedirectFactory->create()->setUrl(
                $this->url->getUrl('checkout/cart/index', ['_secure' => true])
            );
        }

        return $this->resultRedirectFactory->create()->setUrl(
            $this->url->getUrl('checkout/onepage/success', ['_secure' => true])
        );
    }
}

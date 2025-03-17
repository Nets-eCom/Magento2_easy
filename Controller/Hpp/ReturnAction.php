<?php

namespace Nexi\Checkout\Controller\Hpp;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Transaction\Builder;
use NexiCheckout\Api\Exception\PaymentApiException;
use NexiCheckout\Model\Result\RetrievePayment\PaymentStatusEnum;
use NexiCheckout\Model\Result\RetrievePaymentResult;
use Psr\Log\LoggerInterface;
use Nexi\Checkout\Gateway\Http\Client;

class ReturnAction implements ActionInterface
{
    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $request
     * @param UrlInterface $url
     * @param Session $checkoutSession
     * @param Config $config
     * @param Builder $transactionBuilder
     * @param OrderRepository $orderRepository
     * @param LoggerInterface $logger
     * @param ManagerInterface $messageManager
     * @param Client $client
     */
    public function __construct(
        private readonly RedirectFactory  $resultRedirectFactory,
        private readonly RequestInterface $request,
        private readonly UrlInterface     $url,
        private readonly Session          $checkoutSession,
        private readonly Config           $config,
        private readonly Builder          $transactionBuilder,
        private readonly OrderRepository  $orderRepository,
        private readonly LoggerInterface  $logger,
        private readonly ManagerInterface $messageManager,
        private readonly Client           $client,
        private readonly OrderSender      $orderSender
    ) {
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface
     */
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
                throw new LocalizedException(__('Payment already processed'));
            }

            $paymentAction = $this->config->getPaymentAction();
            $paymentId     = $this->request->getParam('paymentid');

            $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
            $paymentTransaction = $this->transactionBuilder->build(
                $paymentId,
                $order,
                ['payment_id' => $paymentId],
                TransactionInterface::TYPE_AUTH
            );

            if (MethodInterface::ACTION_AUTHORIZE) {
                $paymentTransaction->setIsClosed(0);
            }

            $order->addRelatedObject($paymentTransaction);

            $order->addCommentToStatusHistory(__('Nexi Payment authorized successfully. Payment ID: %1', $paymentId));
            $this->orderRepository->save($order);

            if (MethodInterface::ACTION_AUTHORIZE_CAPTURE == $paymentAction) {
                $paymentDetails = $this->getPaymentDetails($paymentId);

                if ($paymentDetails->getPayment()->getStatus() == PaymentStatusEnum::RESERVED) {
                    $this->messageManager->addNoticeMessage(__('Payment reserved, but not charged yet.'));
                    $this->logger->notice('Payment reserved, but not charged yet. Redirecting to success page.');

                    return $this->getSuccessRedirect();
                } elseif ($paymentDetails->getPayment()->getStatus() == PaymentStatusEnum::CHARGED) {
                    $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                    $chargeTxnId       = $paymentDetails->getPayment()->getCharges()[0]->getChargeId();
                    $this->transactionBuilder
                        ->build(
                            $chargeTxnId,
                            $order,
                            [
                                'payment_id' => $paymentId,
                                'charge_id'  => $chargeTxnId,
                            ],
                            TransactionInterface::TYPE_CAPTURE
                        )->setParentId($paymentTransaction->getTransactionId())
                        ->setParentTxnId($paymentTransaction->getTxnId());

                    $invoice = $order->prepareInvoice();
                    $invoice->register();
                    $invoice->setTransactionId($chargeTxnId);
                    $invoice->pay();

                    $order->addCommentToStatusHistory(
                        __('Nexi Payment charged successfully. Payment ID: %1', $paymentId)
                    );
                    $order->addRelatedObject($invoice);
                    $this->orderRepository->save($order);
                    $this->orderSender->send($order);
                }
            }
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage(), [$e]);
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->getCartRedirect();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), [$e]);
            $this->messageManager->addErrorMessage(
                __('An error occurred during the payment process. Please try again later.')
            );
            return $this->getCartRedirect();
        }

        return $this->getSuccessRedirect();
    }

    /**
     * Get payment details from Nexi API
     *
     * @param string $paymentId
     *
     * @return RetrievePaymentResult
     * @throws PaymentApiException
     */
    private function getPaymentDetails(string $paymentId): RetrievePaymentResult
    {
        return $this->client->getPaymentApi()->retrievePayment($paymentId);
    }

    /**
     * Get success redirect
     *
     * @return Redirect
     */
    public function getSuccessRedirect(): Redirect
    {
        return $this->resultRedirectFactory->create()->setUrl(
            $this->url->getUrl('checkout/onepage/success', ['_secure' => true])
        );
    }

    /**
     * Get cart redirect
     *
     * @return Redirect
     */
    public function getCartRedirect(): Redirect
    {
        return $this->resultRedirectFactory->create()->setUrl(
            $this->url->getUrl('checkout/cart/index', ['_secure' => true])
        );
    }
}

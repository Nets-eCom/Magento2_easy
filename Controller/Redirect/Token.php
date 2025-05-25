<?php

namespace Nexi\Checkout\Controller\Redirect;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Nexi\Checkout\Exceptions\CheckoutException;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Gateway\Validator\HmacValidator;
use Nexi\Checkout\Model\Receipt\ProcessService;
use Nexi\Checkout\Model\ReceiptDataProvider;
use Nexi\Checkout\Model\Recurring\TotalConfigProvider;
use Nexi\Checkout\Model\Subscription\SubscriptionCreate;

class Token implements HttpPostActionInterface
{
    /**
     * @var Phrase
     */
    private Phrase $errorMsg;

    /**
     * Token constructor.
     *
     * @param ReceiptDataProvider $receiptDataProvider
     * @param Config $gatewayConfig
     * @param RequestInterface $request
     * @param OrderFactory $orderFactory
     * @param Session $checkoutSession
     * @param CustomerSession $customerSession
     * @param JsonFactory $jsonFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderManagementInterface $orderManagementInterface
     * @param SubscriptionCreate $subscriptionCreate
     * @param CommandManagerPoolInterface $commandManagerPool
     * @param ProcessService $processService
     * @param TotalConfigProvider $totalConfigProvider
     */
    public function __construct(
        private ReceiptDataProvider      $receiptDataProvider,
        private Config                   $gatewayConfig,
        private RequestInterface         $request,
        private OrderFactory             $orderFactory,
        private Session                  $checkoutSession,
        private CustomerSession          $customerSession,
        private JsonFactory              $jsonFactory,
        private OrderRepositoryInterface $orderRepository,
        private OrderManagementInterface $orderManagementInterface,
        private SubscriptionCreate       $subscriptionCreate,
        private CommandManagerPoolInterface $commandManagerPool,
        private ProcessService $processService,
        private TotalConfigProvider $totalConfigProvider
    ) {
    }

    /**
     * Execute function
     *
     * @return ResponseInterface|Json|ResultInterface
     * @throws CheckoutException
     * @throws LocalizedException
     * @throws CouldNotSaveException
     */
    public function execute() // there is also other call which changes order status
    {
        $selectedTokenRaw = $this->request->getParam('selected_token');
        $selectedTokenId = preg_replace('/[^0-9a-f]{2,}$/', '', $selectedTokenRaw);

        if (empty($selectedTokenId)) {
            $this->errorMsg = __('No payment token selected');
            throw new LocalizedException(__('No payment token selected'));
        }

        $order = $this->orderFactory->create();
        $order = $order->loadByIncrementId(
            $this->checkoutSession->getLastRealOrderId()
        );

        $resultJson = $this->jsonFactory->create();
        if ($order->getStatus() === Order::STATE_PROCESSING) {
            $this->errorMsg = __('Payment already processed');
            return $resultJson->setData(
                [
                    'success' => false,
                    'message' => $this->errorMsg
                ]
            );
        }

        $customer = $this->customerSession->getCustomer();
        try {
            $responseData = $this->getTokenResponseData($order, $selectedTokenId, $customer);
            if ($this->totalConfigProvider->isRecurringPaymentEnabled()) {
                if ($this->subscriptionCreate->getSubscriptionSchedule($order) && $responseData->getTransactionId()) {
                    $orderSchedule = $this->subscriptionCreate->getSubscriptionSchedule($order);
                    $this->subscriptionCreate->createSubscription(
                        $orderSchedule,
                        $selectedTokenId,
                        $customer->getId(),
                        $order->getId()
                    );
                }
            }
        } catch (CheckoutException $exception) {
            $this->errorMsg = __('Error processing token payment');
            if ($order) {
                $this->orderManagementInterface->cancel($order->getId());
                $order->addCommentToStatusHistory(
                    __('Order canceled. Failed to process token payment.')
                );
                $this->orderRepository->save($order);
            }

            $this->checkoutSession->restoreQuote();

            return $resultJson->setData(
                [
                    'success' => false,
                    'message' => $this->errorMsg
                ]
            );
        }

        $redirectUrl = $responseData->getThreeDSecureUrl();
        $resultJson = $this->jsonFactory->create();

        if ($redirectUrl) {
            return $resultJson->setData(
                [
                    'success' => true,
                    'data' => 'redirect',
                    'redirect' => $redirectUrl
                ]
            );
        }

        /* fetch payment response using transaction id */
        $response = $this->getPaymentData($responseData->getTransactionId());

        $receiptData = [
            'checkout-account' => $this->gatewayConfig->getMerchantId(),
            'checkout-algorithm' => 'sha256',
            'checkout-amount' => $response['data']->getAmount(),
            'checkout-stamp' => $response['data']->getStamp(),
            'checkout-reference' => $response['data']->getReference(),
            'checkout-transaction-id' => $response['data']->getTransactionId(),
            'checkout-status' => $response['data']->getStatus(),
            'checkout-provider' => $response['data']->getProvider(),
            'signature' => HmacValidator::SKIP_HMAC_VALIDATION
        ];

        $this->receiptDataProvider->execute($receiptData);

        return $resultJson->setData(
            [
                'success' => true,
                'data' => 'redirect',
                'reference' => $response['data']->getReference(),
                'redirect' => $redirectUrl
            ]
        );
    }

    /**
     * GetTokenResponseData function
     *
     * @param Order $order
     * @param string $tokenId
     * @param Customer $customer
     * @return mixed
     * @throws CheckoutException
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    private function getTokenResponseData($order, $tokenId, $customer)
    {
        $commandExecutor = $this->commandManagerPool->get('paytrail');
        $response = $commandExecutor->executeByCode(
            'token_payment',
            null,
            [
                'order' => $order,
                'token_id' => $tokenId,
                'customer' => $customer
            ]
        );

        $errorMsg = $response['error'];

        if (isset($errorMsg)) {
            $this->errorMsg = ($errorMsg);
            $this->processService->processError($errorMsg);
        }

        return $response["data"];
    }

    /**
     * GetPaymentData function
     *
     * @param string $transactionId
     * @return mixed
     * @throws CheckoutException
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    private function getPaymentData($transactionId)
    {
        $commandExecutor = $this->commandManagerPool->get('paytrail');
        $response = $commandExecutor->executeByCode(
            'get_payment_data',
            null,
            [
                'transaction_id' => $transactionId
            ]
        );

        $errorMsg = $response['error'];

        if (isset($errorMsg)) {
            $this->errorMsg = ($errorMsg);
            $this->processService->processError($errorMsg);
        }

        return $response;
    }
}

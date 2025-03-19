<?php

namespace Nexi\Checkout\Test\Unit\Controller\Hpp;

use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Controller\Hpp\ReturnAction;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Transaction\Builder;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Nexi\Checkout\Gateway\Http\Client;
use PHPUnit\Framework\TestCase;

class ReturnActionTest extends TestCase
{
    private $controller;
    private $redirectFactory;
    private $request;
    private $url;
    private $checkoutSession;
    private $config;
    private $transactionBuilder;
    private $orderRepository;
    private $logger;
    private $messageManager;
    private $client;
    private $order;
    private $payment;

    protected function setUp(): void
    {
        $this->redirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->onlyMethods(['create'])
            ->addMethods(['setUrl'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->url = $this->createMock(UrlInterface::class);
        $this->checkoutSession = $this->createMock(Session::class);
        $this->config = $this->createMock(Config::class);
        $this->transactionBuilder = $this->createMock(Builder::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->payment = $this->getMockBuilder(Order\Payment::class)
            ->onlyMethods(['getAdditionalInformation'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->order = $this->getMockBuilder(Order::class)
            ->onlyMethods(['getPayment', 'getState', 'setState', 'getStatus', 'setStatus', 'addCommentToStatusHistory'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->order->method('getPayment')->willReturn($this->payment);
        $this->checkoutSession->method('getLastRealOrder')->willReturn($this->order);

        $redirect = $this->createMock(Redirect::class);
        $redirect->method('setUrl')->willReturnSelf();
        $this->redirectFactory->method('create')->willReturn($redirect);
        $this->url->method('getUrl')->willReturn('checkout/onepage/success');

        $this->controller = new ReturnAction(
            $this->redirectFactory,
            $this->request,
            $this->url,
            $this->checkoutSession,
            $this->config,
            $this->transactionBuilder,
            $this->orderRepository,
            $this->logger,
            $this->messageManager,
            $this->client
        );
    }

    public function testExecutePaymentIdMismatch()
    {
        $this->request->method('getParam')->with('paymentid')->willReturn('wrong_id');
        $this->order->method('getPayment')->willReturn($this->payment);

        $result = $this->controller->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecutePaymentAlreadyProcessed()
    {
        $this->order->method('getState')->willReturn(Order::STATE_PROCESSING);
        $this->messageManager->expects($this->once())->method('addNoticeMessage');
        $this->request->method('getParam')->with('paymentid')->willReturn('correct_id');
        $this->payment->method('getAdditionalInformation')->willReturn('correct_id');

        $result = $this->controller->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testExecuteSuccessRedirect()
    {
        $this->order->method('getState')->willReturn(Order::STATE_NEW);
        $this->order->method('setState')->willReturnSelf();
        $this->order->method('setStatus')->willReturnSelf();
        $this->order->method('addCommentToStatusHistory')->willReturnSelf();

        $this->request->method('getParam')->with('paymentid')->willReturn('correct_id');
        $this->payment->method('getAdditionalInformation')->willReturn('correct_id');

        $transactionMock = $this->getMockBuilder(Order\Payment\Transaction::class)
            ->onlyMethods([
                             'setIsClosed',
                             'setTransactionId',
                             'setParentId',
                             'setParentTxnId',
                             'getTransactionId',
                             'getTxnId'
                         ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionBuilder->method('build')->willReturn($transactionMock);
        $result = $this->controller->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }
}

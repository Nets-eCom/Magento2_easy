<?php

declare(strict_types=1);

namespace Nexi\Checkout\Test\Unit\Controller\Hpp;

use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderManagementInterface;
use Nexi\Checkout\Controller\Hpp\CancelAction;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CancelActionTest extends TestCase
{
    /**
     * @var CancelAction
     */
    private $controller;

    /**
     * @var RedirectFactory
     */
    private $redirectFactoryMock;

    /**
     * @var Redirect
     */
    private $redirectMock;

    /**
     * @var UrlInterface
     */
    private $urlMock;

    /**
     * @var LoggerInterface
     */
    private $loggerMock;

    /**
     * @var Session
     */
    private $checkoutSessionMock;

    /**
     * @var ManagerInterface
     */
    private $messageManagerMock;

    /**
     * @var Quote
     */
    private $quoteMock;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagementInterfaceMock;

    protected function setUp(): void
    {
        $this->redirectMock = $this->createMock(Redirect::class);
        $this->redirectFactoryMock = $this->createMock(RedirectFactory::class);
        $this->redirectFactoryMock->method('create')
            ->willReturn($this->redirectMock);
        $this->urlMock = $this->createMock(UrlInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->checkoutSessionMock = $this->createMock(Session::class);
        $this->quoteMock = $this->createMock(Quote::class);
        $this->checkoutSessionMock->method('getQuote')
            ->willReturn($this->quoteMock);
        $this->quoteMock->method('getPayment')
            ->willReturn($this->createMock(\Magento\Quote\Model\Quote\Payment::class));
        $this->messageManagerMock = $this->createMock(ManagerInterface::class);
        $this->orderManagementInterfaceMock = $this->createMock(OrderManagementInterface::class);
        $this->orderManagementInterfaceMock->method('cancel');

        $this->controller = new CancelAction(
            $this->redirectFactoryMock,
            $this->urlMock,
            $this->checkoutSessionMock,
            $this->orderManagementInterfaceMock,
            $this->messageManagerMock
        );
    }

    public function testExecuteSuccess()
    {
        $this->checkoutSessionMock->expects($this->once())
            ->method('restoreQuote');
        $this->orderManagementInterfaceMock->expects($this->once())
            ->method('cancel');
        $this->messageManagerMock->expects($this->once())
            ->method('addNoticeMessage')
            ->with(__('The payment has been canceled.'));
        $this->urlMock->method('getUrl')
            ->willReturn('checkout/cart/index');
        $this->redirectMock->expects($this->once())
            ->method('setUrl')
            ->with('checkout/cart/index')
            ->willReturnSelf();

        $result = $this->controller->execute();
        $this->assertSame($this->redirectMock, $result);
    }
}

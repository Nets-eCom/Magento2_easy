<?php

declare(strict_types=1);

namespace Nexi\Checkout\Test\Unit\Controller\Hpp;

use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Nexi\Checkout\Controller\Hpp\CancelAction;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CancelActionTest extends TestCase
{
    private $controller;
    private $redirectFactoryMock;
    private $redirectMock;
    private $urlMock;
    private $loggerMock;
    private $checkoutSessionMock;
    private $messageManagerMock;
    private $quoteMock;

    protected function setUp(): void
    {
        $this->redirectMock = $this->createMock(Redirect::class);
        $this->redirectFactoryMock = $this->createMock(RedirectFactory::class);
        $this->redirectFactoryMock->method('create')
            ->willReturn($this->redirectMock);
        $this->urlMock = $this->createMock(UrlInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->checkoutSessionMock = $this->createMock(Session::class);
        $this->quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->checkoutSessionMock->method('getQuote')
            ->willReturn($this->quoteMock);
        $this->quoteMock->method('getPayment')
            ->willReturn($this->createMock(\Magento\Quote\Model\Quote\Payment::class));
        $this->messageManagerMock = $this->createMock(ManagerInterface::class);

        $this->controller = new CancelAction(
            $this->redirectFactoryMock,
            $this->urlMock,
            $this->loggerMock,
            $this->checkoutSessionMock,
            $this->messageManagerMock
        );
    }

    public function testExecuteSuccess()
    {
        $this->checkoutSessionMock->expects($this->once())
            ->method('restoreQuote');
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

    public function testExecuteException()
    {
        $this->checkoutSessionMock->method('restoreQuote')
            ->willThrowException(new \Exception('Error restoring quote'));
        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage')
            ->with($this->callback(function ($message) {
                return str_contains(
                    (string) $message,
                    'An error occurred during the payment process. Please try again later.'
                );
            }));
        $this->loggerMock->expects($this->once())
            ->method('critical');
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

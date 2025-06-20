<?php

namespace Nexi\Checkout\Test\Unit\Gateway\Command;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Nexi\Checkout\Gateway\Command\Initialize;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Transaction\Builder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class InitializeTest extends TestCase
{
    /**
     * @var CommandManagerPoolInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $commandManagerPoolMock;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var Builder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transactionBuilderMock;

    /**
     * @var SubjectReader|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subjectReaderMock;

    /**
     * @var Initialize
     */
    private $initialize;

    protected function setUp(): void
    {
        $this->commandManagerPoolMock = $this->createMock(CommandManagerPoolInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->transactionBuilderMock = $this->createMock(Builder::class);
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->initialize = new Initialize(
            $this->subjectReaderMock,
            $this->commandManagerPoolMock,
            $this->loggerMock,
            $this->transactionBuilderMock
        );
    }

    /**
     * Test createPayment method with successful execution
     */
    public function testcreatePaymentSuccess(): void
    {
        // Mock payment data object
        $paymentDataMock = $this->createMock(PaymentDataObjectInterface::class);

        // Setup expectations for commandManagerPool
        $commandManagerMock = $this->createMock(CommandManagerInterface::class);
        $this->commandManagerPoolMock->expects($this->once())
            ->method('get')
            ->with(Config::CODE)
            ->willReturn($commandManagerMock);
        $commandManagerMock->expects($this->once())
            ->method('executeByCode')
            ->with(
                'create_payment',
                null,
                ['payment' => $paymentDataMock]
            );

        // Execute the method
        $this->initialize->createPayment($paymentDataMock);
    }

    /**
     * Test createPayment method with exception
     */
    public function testcreatePaymentException(): void
    {
        // Mock payment data object
        $paymentDataMock = $this->createMock(PaymentDataObjectInterface::class);

        // Setup expectations for commandManagerPool to throw exception
        $commandManagerMock = $this->createMock(CommandManagerInterface::class);
        $this->commandManagerPoolMock->expects($this->once())
            ->method('get')
            ->with(Config::CODE)
            ->willReturn($commandManagerMock);
        $commandManagerMock->expects($this->once())
            ->method('executeByCode')
            ->with(
                'create_payment',
                null,
                ['payment' => $paymentDataMock]
            )
            ->willThrowException(new Exception('Test exception'));

        // Setup expectations for logger
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Test exception', $this->anything());

        // Execute the method and expect exception
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('An error occurred during the payment process. Please try again later.');
        $this->initialize->createPayment($paymentDataMock);
    }

    /**
     * Test isPaymentAlreadyCreated method when payment ID exists
     */
    public function testIsPaymentAlreadyCreatedTrue(): void
    {
        // Use reflection to test private method
        $reflectionClass = new \ReflectionClass(Initialize::class);
        $method = $reflectionClass->getMethod('isPaymentAlreadyCreated');
        $method->setAccessible(true);

        // Mock payment data object
        $paymentDataMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentMock = $this->createMock(Payment::class);

        // Setup expectations
        $paymentDataMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);
        $paymentMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->with('payment_id')
            ->willReturn('existing-payment-id');

        // Execute the method
        $result = $method->invoke($this->initialize, $paymentDataMock);
        $this->assertTrue($result);
    }

    /**
     * Test isPaymentAlreadyCreated method when payment ID does not exist
     */
    public function testIsPaymentAlreadyCreatedFalse(): void
    {
        // Use reflection to test private method
        $reflectionClass = new \ReflectionClass(Initialize::class);
        $method = $reflectionClass->getMethod('isPaymentAlreadyCreated');
        $method->setAccessible(true);

        // Mock payment data object
        $paymentDataMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentMock = $this->createMock(Payment::class);

        // Setup expectations
        $paymentDataMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);
        $paymentMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->with('payment_id')
            ->willReturn(null);

        // Execute the method
        $result = $method->invoke($this->initialize, $paymentDataMock);
        $this->assertFalse($result);
    }
}

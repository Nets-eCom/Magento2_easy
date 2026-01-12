<?php
declare(strict_types=1);

namespace Nexi\Checkout\Test\Unit\Gateway\Http;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Gateway\Http\Client;
use NexiCheckout\Api\Exception\PaymentApiException;
use NexiCheckout\Api\PaymentApi;
use NexiCheckout\Factory\PaymentApiFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var PaymentApiFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentApiFactoryMock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var PaymentApi|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentApiMock;

    protected function setUp(): void
    {
        $this->paymentApiFactoryMock = $this->createMock(PaymentApiFactory::class);
        $this->configMock = $this->createMock(Config::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        // Create a mock for PaymentApi with the testMethod method
        $this->paymentApiMock = $this->getMockBuilder(PaymentApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        // We'll set up the testMethod in each test

        $this->client = new Client(
            $this->paymentApiFactoryMock,
            $this->configMock,
            $this->loggerMock
        );
    }

    /**
     * Test getPaymentApi method
     */
    public function testGetPaymentApi(): void
    {
        // Setup expectations for config
        $this->configMock->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');
        $this->configMock->expects($this->once())
            ->method('isLiveMode')
            ->willReturn(false);

        // Setup expectations for payment API factory
        $this->paymentApiFactoryMock->expects($this->once())
            ->method('create')
            ->with('test-api-key', false)
            ->willReturn($this->paymentApiMock);

        // Execute the method
        $result = $this->client->getPaymentApi();
        $this->assertSame($this->paymentApiMock, $result);
    }

    /**
     * Test placeRequest method with exception
     */
    public function testPlaceRequestWithException(): void
    {
        // Create mock for transfer object
        $transferMock = $this->createMock(TransferInterface::class);
        $transferMock->expects($this->once())
            ->method('getUri')
            ->willReturn('retrievePayment');
        $transferMock->expects($this->exactly(4))
            ->method('getBody')
            ->willReturn('test-body');

        // Set up the PaymentApi mock
        $this->paymentApiMock = $this->getMockBuilder(PaymentApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set up the retrievePayment method to throw an exception
        $this->paymentApiMock->expects($this->once())
            ->method('retrievePayment')
            ->with('test-body')
            ->willThrowException(new PaymentApiException('Test exception'));

        // Setup expectations for payment API factory
        $this->paymentApiFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->paymentApiMock);

        // Setup expectations for config
        $this->configMock->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key');
        $this->configMock->expects($this->once())
            ->method('isLiveMode')
            ->willReturn(false);

        // Setup expectations for logger
        $this->loggerMock->expects($this->once())
            ->method('debug');
        $this->loggerMock->expects($this->once())
            ->method('error');

        // Execute the method and expect exception
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('An error occurred during the payment process. Please try again later.');
        $this->client->placeRequest($transferMock);
    }
}

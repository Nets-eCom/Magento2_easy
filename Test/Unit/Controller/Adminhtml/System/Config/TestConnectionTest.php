<?php

    declare(strict_types=1);

    namespace Nexi\Checkout\Test\Unit\Controller\Adminhtml\System\Config;

    use Magento\Backend\App\Action\Context;
    use Magento\Framework\App\RequestInterface;
    use Magento\Framework\Controller\Result\Json;
    use Magento\Framework\Controller\Result\JsonFactory;
    use Magento\Framework\Filter\StripTags;
    use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
    use Nexi\Checkout\Controller\Adminhtml\System\Config\TestConnection;
    use Nexi\Checkout\Gateway\Config\Config;
    use Nexi\Checkout\Model\Config\Source\Environment;
    use NexiCheckout\Api\PaymentApi;
    use NexiCheckout\Factory\PaymentApiFactory;
    use PHPUnit\Framework\TestCase;

    class TestConnectionTest extends TestCase
    {
        private $controller;
        private $jsonFactoryMock;
        private $jsonMock;
        private $requestMock;
        private $paymentApiFactoryMock;
        private $configMock;

        protected function setUp(): void
        {
            $objectManager = new ObjectManager($this);

            $contextMock = $this->createMock(Context::class);
            $this->requestMock = $this->createMock(RequestInterface::class);
            $this->jsonFactoryMock = $this->createMock(JsonFactory::class);
            $this->jsonMock = $this->createMock(Json::class);
            $this->paymentApiFactoryMock = $this->createMock(PaymentApiFactory::class);
            $this->configMock = $this->createMock(Config::class);
            $stripTagsMock = $this->createMock(StripTags::class);

            $contextMock->method('getRequest')->willReturn($this->requestMock);
            $this->jsonFactoryMock->method('create')->willReturn($this->jsonMock);

            $this->controller = $objectManager->getObject(
                TestConnection::class,
                [
                    'context' => $contextMock,
                    'resultJsonFactory' => $this->jsonFactoryMock,
                    'tagFilter' => $stripTagsMock,
                    'paymentApiFactory' => $this->paymentApiFactoryMock,
                    'config' => $this->configMock
                ]
            );
        }

        public function testExecuteSuccess()
        {
            $this->requestMock->method('getParams')->willReturn([
                'api_key' => 'valid_api_key',
                'environment' => Environment::LIVE
            ]);

            $apiMock = $this->createMock(PaymentApi::class);
            $this->paymentApiFactoryMock->method('create')->willReturn($apiMock);
            $apiMock->method('retrievePayment')->willThrowException(new \Exception('should be in guid format'));

            $this->jsonMock->expects($this->once())
                ->method('setData')
                ->with(['success' => true, 'errorMessage' => ''])
                ->willReturnSelf();

            $this->controller->execute();
        }

        public function testExecuteFailure()
        {
            $this->requestMock->method('getParams')->willReturn([
                'api_key' => 'invalid_api_key',
                'environment' => Environment::LIVE
            ]);

            $apiMock = $this->createMock(PaymentApi::class);
            $this->paymentApiFactoryMock->method('create')->willReturn($apiMock);
            $apiMock->method('retrievePayment')->willThrowException(new \Exception('Invalid API key'));

            $this->jsonMock->expects($this->once())
                ->method('setData')
                ->with(['success' => false, 'errorMessage' => 'Please check your API key and environment.'])
                ->willReturnSelf();

            $this->controller->execute();
        }

        // Test for missing parameters
        public function testExecuteMissingParams()
        {
            $this->requestMock->method('getParams')->willReturn([]);

            $this->jsonMock->expects($this->once())
                ->method('setData')
                ->with(['success' => false, 'errorMessage' => 'API key and environment are required.'])
                ->willReturnSelf();

            $this->controller->execute();
        }

        // write a new test case for invalid api key
        public function testExecuteInvalidApiKey()
        {
            $this->requestMock->method('getParams')->willReturn([
                'api_key' => 'invalid_api_key',
                'environment' => Environment::LIVE
            ]);

            $apiMock = $this->createMock(PaymentApi::class);
            $this->paymentApiFactoryMock->method('create')->willReturn($apiMock);
            $apiMock->method('retrievePayment')->willThrowException(new \Exception('Invalid API key'));

            $this->jsonMock->expects($this->once())
                ->method('setData')
                ->with(['success' => false, 'errorMessage' => 'Invalid API key'])
                ->willReturnSelf();

            $this->controller->execute();
        }
    }

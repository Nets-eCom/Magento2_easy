<?php
declare(strict_types=1);

namespace Nexi\Checkout\Test\Unit\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filter\StripTags;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Url;
use Magento\Store\Model\ScopeInterface;
use Nexi\Checkout\Controller\Adminhtml\System\Config\TestConnection;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Config\Source\Environment;
use NexiCheckout\Api\PaymentApi;
use NexiCheckout\Factory\PaymentApiFactory;
use PHPUnit\Framework\TestCase;

class TestConnectionTest extends TestCase
{
    /**
     * @var TestConnection
     */
    private $controller;

    /**
     * @var JsonFactory
     */
    private $jsonFactoryMock;

    /**
     * @var Json
     */
    private $jsonMock;

    /**
     * @var RequestInterface
     */
    private $requestMock;

    /**
     * @var PaymentApiFactory
     */
    private $paymentApiFactoryMock;

    /**
     * @var Config
     */
    private $configMock;

    /**
     * @var Url
     */
    private $urlMock;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $contextMock                 = $this->createMock(Context::class);
        $this->requestMock           = $this->createMock(RequestInterface::class);
        $this->jsonFactoryMock       = $this->createMock(JsonFactory::class);
        $this->jsonMock              = $this->createMock(Json::class);
        $this->paymentApiFactoryMock = $this->createMock(PaymentApiFactory::class);
        $this->configMock            = $this->createMock(Config::class);
        $this->urlMock               = $this->createMock(Url::class);
        $this->scopeConfigMock       = $this->createMock(ScopeConfigInterface::class);
        $stripTagsMock               = $this->createMock(StripTags::class);

        $contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->jsonFactoryMock->method('create')->willReturn($this->jsonMock);

        $this->scopeConfigMock->method('getValue')->with(
            'currency/options/default',
            ScopeInterface::SCOPE_STORE
        )->willReturn('USD');

        $this->urlMock->method('getUrl')->willReturn('https://example.com');

        $this->controller = $objectManager->getObject(
            TestConnection::class,
            [
                'context'           => $contextMock,
                'resultJsonFactory' => $this->jsonFactoryMock,
                'tagFilter'         => $stripTagsMock,
                'paymentApiFactory' => $this->paymentApiFactoryMock,
                'config'            => $this->configMock,
                'url'               => $this->urlMock,
                'scopeConfig'       => $this->scopeConfigMock
            ]
        );
    }

    public function testExecuteSuccess()
    {
        $this->requestMock->method('getParams')->willReturn([
                                                                'secret_key'     => 'valid_api_key',
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
                                                                'secret_key'     => 'invalid_api_key',
                                                                'environment' => Environment::LIVE
                                                            ]);

        $apiMock = $this->createMock(PaymentApi::class);
        $this->paymentApiFactoryMock->method('create')->willReturn($apiMock);
        $apiMock->method('createEmbeddedPayment')->willThrowException(new \NexiCheckout\Api\Exception\PaymentApiException('Invalid API key'));

        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->with(['success' => false, 'errorMessage' => ' Please check your API key and environment.'])
            ->willReturnSelf();

        $this->controller->execute();
    }
}

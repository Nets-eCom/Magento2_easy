<?php

namespace Nexi\Checkout\Test\Unit\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Config\Source\Environment;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config(
            $this->scopeConfigMock,
            Config::CODE
        );
    }

    /**
     * Test getEnvironment method
     */
    public function testGetEnvironment(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('payment/nexi/environment')
            ->willReturn(Environment::LIVE);

        $this->assertEquals(Environment::LIVE, $this->config->getEnvironment());
    }

    /**
     * Test isLiveMode method when environment is live
     */
    public function testIsLiveModeTrueWhenEnvironmentIsLive(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('payment/nexi/environment')
            ->willReturn(Environment::LIVE);

        $this->assertTrue($this->config->isLiveMode());
    }

    /**
     * Test isLiveMode method when environment is not live
     */
    public function testIsLiveModeFalseWhenEnvironmentIsNotLive(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('payment/nexi/environment')
            ->willReturn(Environment::TEST);

        $this->assertFalse($this->config->isLiveMode());
    }

    /**
     * Test isActive method
     */
    public function testIsActive(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('payment/nexi/active')
            ->willReturn('1');

        $this->assertTrue($this->config->isActive());
    }

    /**
     * Test getApiKey method when in live mode
     */
    public function testGetApiKeyInLiveMode(): void
    {
        $this->scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                ['payment/nexi/environment', ScopeInterface::SCOPE_STORE, null, Environment::LIVE],
                ['payment/nexi/secret_key', ScopeInterface::SCOPE_STORE, null, 'live-api-key']
            ]);

        $this->assertEquals('live-api-key', $this->config->getApiKey());
    }

    /**
     * Test getApiKey method when in test mode
     */
    public function testGetApiKeyInTestMode(): void
    {
        $this->scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                ['payment/nexi/environment', ScopeInterface::SCOPE_STORE, null, Environment::TEST],
                ['payment/nexi/test_secret_key', ScopeInterface::SCOPE_STORE, null, 'test-api-key']
            ]);

        $this->assertEquals('test-api-key', $this->config->getApiKey());
    }

    /**
     * Test getCheckoutKey method when in live mode
     */
    public function testGetCheckoutKeyInLiveMode(): void
    {
        $this->scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                ['payment/nexi/environment', ScopeInterface::SCOPE_STORE, null, Environment::LIVE],
                ['payment/nexi/checkout_key', ScopeInterface::SCOPE_STORE, null, 'live-checkout-key']
            ]);

        $this->assertEquals('live-checkout-key', $this->config->getCheckoutKey());
    }

    /**
     * Test getCheckoutKey method when in test mode
     */
    public function testGetCheckoutKeyInTestMode(): void
    {
        $this->scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                ['payment/nexi/environment', ScopeInterface::SCOPE_STORE, null, Environment::TEST],
                ['payment/nexi/test_checkout_key', ScopeInterface::SCOPE_STORE, null, 'test-checkout-key']
            ]);

        $this->assertEquals('test-checkout-key', $this->config->getCheckoutKey());
    }

    /**
     * Test isEmbedded method when integration type is embedded
     */
    public function testIsEmbeddedTrue(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('payment/nexi/integration_type')
            ->willReturn(IntegrationTypeEnum::EmbeddedCheckout->name);

        $this->assertTrue($this->config->isEmbedded());
    }

    /**
     * Test isEmbedded method when integration type is not embedded
     */
    public function testIsEmbeddedFalse(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('payment/nexi/integration_type')
            ->willReturn(IntegrationTypeEnum::HostedPaymentPage->name);

        $this->assertFalse($this->config->isEmbedded());
    }

    /**
     * Test getPaymentAction method when auto capture is enabled
     */
    public function testGetPaymentActionWithAutoCapture(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('payment/nexi/is_auto_capture')
            ->willReturn('1');

        $this->assertEquals(MethodInterface::ACTION_AUTHORIZE_CAPTURE, $this->config->getPaymentAction());
    }

    /**
     * Test getPaymentAction method when auto capture is disabled
     */
    public function testGetPaymentActionWithoutAutoCapture(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('payment/nexi/is_auto_capture')
            ->willReturn('0');

        $this->assertEquals(MethodInterface::ACTION_AUTHORIZE, $this->config->getPaymentAction());
    }
}

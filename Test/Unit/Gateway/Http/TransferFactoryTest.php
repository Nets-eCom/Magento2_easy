<?php

namespace Nexi\Checkout\Test\Unit\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferInterface;
use Nexi\Checkout\Gateway\Http\TransferFactory;
use PHPUnit\Framework\TestCase;

class TransferFactoryTest extends TestCase
{
    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * @var TransferBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transferBuilderMock;

    /**
     * @var TransferInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transferMock;

    protected function setUp(): void
    {
        $this->transferBuilderMock = $this->createMock(TransferBuilder::class);
        $this->transferMock = $this->createMock(TransferInterface::class);
        
        $this->transferFactory = new TransferFactory($this->transferBuilderMock);
    }

    /**
     * Test create method
     */
    public function testCreate(): void
    {
        // Prepare test data
        $request = [
            'nexi_method' => 'testMethod',
            'body' => ['param1' => 'value1', 'param2' => 'value2']
        ];

        // Setup expectations for transfer builder
        $this->transferBuilderMock->expects($this->once())
            ->method('setBody')
            ->with($request['body'])
            ->willReturnSelf();
        $this->transferBuilderMock->expects($this->once())
            ->method('setUri')
            ->with('testMethod')
            ->willReturnSelf();
        $this->transferBuilderMock->expects($this->once())
            ->method('build')
            ->willReturn($this->transferMock);

        // Execute the method
        $result = $this->transferFactory->create($request);
        
        // Verify the result
        $this->assertSame($this->transferMock, $result);
    }
}

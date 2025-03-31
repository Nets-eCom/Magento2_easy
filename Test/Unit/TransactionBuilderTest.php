<?php

namespace Nexi\Checkout\Test\Unit\Model\Transaction;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Transaction\TransactionBuilder;
use PHPUnit\Framework\TestCase;

class TransactionBuilderTest extends TestCase
{
    private $transactionBuilderMock;
    private $configMock;
    private $builder;

    protected function setUp(): void
    {
        $this->transactionBuilderMock = $this->createMock(BuilderInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->builder = new TransactionBuilder($this->transactionBuilderMock, $this->configMock);
    }

    public function testBuildTransactionSuccessfully()
    {
        $transactionId = '12345';
        $order = $this->createMock(Order::class);
        $transactionData = ['key' => 'value'];
        $action = 'capture';

        $order->method('getPayment')->willReturn($this->createMock(Order\Payment::class));
        $this->transactionBuilderMock->method('setOrder')->willReturnSelf();
        $this->transactionBuilderMock->method('setPayment')->willReturnSelf();
        $this->transactionBuilderMock->method('setTransactionId')->willReturnSelf();
        $this->transactionBuilderMock->method('setAdditionalInformation')->willReturnSelf();
        $this->transactionBuilderMock->method('setFailSafe')->willReturnSelf();
        $this->transactionBuilderMock->method('setMessage')->willReturnSelf();
        $this->transactionBuilderMock->method('build')->willReturn($this->createMock(TransactionInterface::class));

        $result = $this->builder->build($transactionId, $order, $transactionData, $action);

        $this->assertInstanceOf(TransactionInterface::class, $result);
    }

    public function testBuildTransactionWithNullAction()
    {
        $transactionId = '12345';
        $order = $this->createMock(Order::class);
        $transactionData = ['key' => 'value'];
        $action = null;

        $order->method('getPayment')->willReturn($this->createMock(Order\Payment::class));
        $this->transactionBuilderMock->method('setOrder')->willReturnSelf();
        $this->transactionBuilderMock->method('setPayment')->willReturnSelf();
        $this->transactionBuilderMock->method('setTransactionId')->willReturnSelf();
        $this->transactionBuilderMock->method('setAdditionalInformation')->willReturnSelf();
        $this->transactionBuilderMock->method('setFailSafe')->willReturnSelf();
        $this->transactionBuilderMock->method('setMessage')->willReturnSelf();
        $this->transactionBuilderMock->method('build')->willReturn($this->createMock(TransactionInterface::class));

        $this->configMock->method('getPaymentAction')->willReturn('authorize');

        $result = $this->builder->build($transactionId, $order, $transactionData, $action);

        $this->assertInstanceOf(TransactionInterface::class, $result);
    }

    public function testBuildTransactionWithNullTransactionData()
    {
        $transactionId = '12345';
        $order = $this->createMock(Order::class);
        $transactionData = null;
        $action = 'capture';

        $order->method('getPayment')->willReturn($this->createMock(Order\Payment::class));
        $this->transactionBuilderMock->method('setOrder')->willReturnSelf();
        $this->transactionBuilderMock->method('setPayment')->willReturnSelf();
        $this->transactionBuilderMock->method('setTransactionId')->willReturnSelf();
        $this->transactionBuilderMock->method('setAdditionalInformation')->willReturnSelf();
        $this->transactionBuilderMock->method('setFailSafe')->willReturnSelf();
        $this->transactionBuilderMock->method('setMessage')->willReturnSelf();
        $this->transactionBuilderMock->method('build')->willReturn($this->createMock(TransactionInterface::class));

        $result = $this->builder->build($transactionId, $order, $transactionData, $action);

        $this->assertInstanceOf(TransactionInterface::class, $result);
    }
}

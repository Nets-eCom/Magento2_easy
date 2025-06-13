<?php

namespace Nexi\Checkout\Test\Unit\Model\Webhook\Data;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use PHPUnit\Framework\TestCase;

class WebhookDataLoaderTest extends TestCase
{
    /**
     * @var SearchCriteriaBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var TransactionRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transactionRepositoryMock;

    /**
     * @var WebhookDataLoader
     */
    private $webhookDataLoader;

    protected function setUp(): void
    {
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->transactionRepositoryMock = $this->createMock(TransactionRepositoryInterface::class);

        $this->webhookDataLoader = new WebhookDataLoader(
            $this->searchCriteriaBuilderMock,
            $this->transactionRepositoryMock
        );
    }

    public function testGetTransactionByPaymentIdReturnsTransactionWhenFound(): void
    {
        $txnId = 'payment-123';
        $txnType = TransactionInterface::TYPE_PAYMENT;

        // Mock search criteria
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $searchResultMock = $this->createMock(TransactionSearchResultInterface::class);
        $transactionMock = $this->createMock(TransactionInterface::class);

        // Setup expectations for search criteria builder
        $this->searchCriteriaBuilderMock->expects($this->exactly(2))
            ->method('addFilter')
            ->withConsecutive(
                ['txn_id', $txnId, 'eq'],
                ['txn_type', $txnType, 'eq']
            )
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        // Setup expectations for transaction repository
        $this->transactionRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultMock);
        $searchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$transactionMock]);

        // Execute the method
        $result = $this->webhookDataLoader->getTransactionByPaymentId($txnId, $txnType);

        // Assert result
        $this->assertSame($transactionMock, $result);
    }

    public function testGetTransactionByPaymentIdReturnsNullWhenNotFound(): void
    {
        $txnId = 'payment-123';
        $txnType = TransactionInterface::TYPE_PAYMENT;

        // Mock search criteria
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $searchResultMock = $this->createMock(TransactionSearchResultInterface::class);

        // Setup expectations for search criteria builder
        $this->searchCriteriaBuilderMock->expects($this->exactly(2))
            ->method('addFilter')
            ->withConsecutive(
                ['txn_id', $txnId, 'eq'],
                ['txn_type', $txnType, 'eq']
            )
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        // Setup expectations for transaction repository
        $this->transactionRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultMock);
        $searchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        // Execute the method
        $result = $this->webhookDataLoader->getTransactionByPaymentId($txnId, $txnType);

        // Assert result
        $this->assertNull($result);
    }

    public function testGetTransactionByOrderIdReturnsTransactionWhenFound(): void
    {
        $orderId = 123;
        $txnType = TransactionInterface::TYPE_PAYMENT;

        // Mock search criteria
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $searchResultMock = $this->createMock(TransactionSearchResultInterface::class);
        $transactionMock = $this->createMock(TransactionInterface::class);

        // Setup expectations for search criteria builder
        $this->searchCriteriaBuilderMock->expects($this->exactly(2))
            ->method('addFilter')
            ->withConsecutive(
                ['order_id', $orderId, 'eq'],
                ['txn_type', $txnType, 'eq']
            )
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        // Setup expectations for transaction repository
        $this->transactionRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultMock);
        $searchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$transactionMock]);

        // Execute the method
        $result = $this->webhookDataLoader->getTransactionByOrderId($orderId, $txnType);

        // Assert result
        $this->assertSame($transactionMock, $result);
    }

    public function testGetTransactionByOrderIdThrowsExceptionWhenNotFound(): void
    {
        $orderId = 123;
        $txnType = TransactionInterface::TYPE_PAYMENT;

        // Mock search criteria
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $searchResultMock = $this->createMock(TransactionSearchResultInterface::class);

        // Setup expectations for search criteria builder
        $this->searchCriteriaBuilderMock->expects($this->exactly(2))
            ->method('addFilter')
            ->withConsecutive(
                ['order_id', $orderId, 'eq'],
                ['txn_type', $txnType, 'eq']
            )
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        // Setup expectations for transaction repository
        $this->transactionRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultMock);
        $searchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        // Expect exception
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Transaction not found or multiple transactions found for payment ID.');

        // Execute the method
        $this->webhookDataLoader->getTransactionByOrderId($orderId, $txnType);
    }

    public function testGetTransactionByOrderIdThrowsExceptionWhenMultipleFound(): void
    {
        $orderId = 123;
        $txnType = TransactionInterface::TYPE_PAYMENT;

        // Mock search criteria
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $searchResultMock = $this->createMock(TransactionSearchResultInterface::class);
        $transactionMock1 = $this->createMock(TransactionInterface::class);
        $transactionMock2 = $this->createMock(TransactionInterface::class);

        // Setup expectations for search criteria builder
        $this->searchCriteriaBuilderMock->expects($this->exactly(2))
            ->method('addFilter')
            ->withConsecutive(
                ['order_id', $orderId, 'eq'],
                ['txn_type', $txnType, 'eq']
            )
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        // Setup expectations for transaction repository
        $this->transactionRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultMock);
        $searchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$transactionMock1, $transactionMock2]);

        // Expect exception
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Transaction not found or multiple transactions found for payment ID.');

        // Execute the method
        $this->webhookDataLoader->getTransactionByOrderId($orderId, $txnType);
    }

    public function testLoadOrderByPaymentId(): void
    {
        $paymentId = 'payment-123';

        // Mock transaction and order
        $transactionMock = $this->getMockBuilder(TransactionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrder'])
            ->getMockForAbstractClass();
        $orderMock = $this->createMock(Order::class);

        // Mock search criteria
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $searchResultMock = $this->createMock(TransactionSearchResultInterface::class);

        // Setup expectations for search criteria builder
        $this->searchCriteriaBuilderMock->expects($this->exactly(2))
            ->method('addFilter')
            ->withConsecutive(
                ['txn_id', $paymentId, 'eq'],
                ['txn_type', TransactionInterface::TYPE_PAYMENT, 'eq']
            )
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        // Setup expectations for transaction repository
        $this->transactionRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultMock);
        $searchResultMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$transactionMock]);

        // Setup expectations for transaction
        $transactionMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);

        // Execute the method
        $result = $this->webhookDataLoader->loadOrderByPaymentId($paymentId);

        // Assert result
        $this->assertSame($orderMock, $result);
    }
}

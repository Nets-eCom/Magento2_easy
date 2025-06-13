<?php

namespace Nexi\Checkout\Test\Unit\Model\Webhook;

use Magento\Reports\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order\Payment\Collection as PaymentCollection;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory as PaymentCollectionFactory;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use Nexi\Checkout\Model\Webhook\PaymentCreated;
use PHPUnit\Framework\TestCase;

class PaymentCreatedTest extends TestCase
{
    /**
     * @var Builder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transactionBuilderMock;

    /**
     * @var OrderCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var WebhookDataLoader|\PHPUnit\Framework\MockObject\MockObject
     */
    private $webhookDataLoaderMock;

    /**
     * @var OrderRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var PaymentCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentCollectionFactoryMock;

    /**
     * @var Comment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $commentMock;

    /**
     * @var PaymentCreated
     */
    private $paymentCreated;

    protected function setUp(): void
    {
        $this->transactionBuilderMock = $this->createMock(Builder::class);
        $this->orderCollectionFactoryMock = $this->getMockForNonExistingClass(
            'Magento\Reports\Model\ResourceModel\Order\CollectionFactory',
            ['create']
        );
        $this->webhookDataLoaderMock = $this->createMock(WebhookDataLoader::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->paymentCollectionFactoryMock = $this->getMockForNonExistingClass(
            'Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory',
            ['create']
        );
        $this->commentMock = $this->createMock(Comment::class);

        $this->paymentCreated = new PaymentCreated(
            $this->transactionBuilderMock,
            $this->orderCollectionFactoryMock,
            $this->webhookDataLoaderMock,
            $this->orderRepositoryMock,
            $this->paymentCollectionFactoryMock,
            $this->commentMock
        );
    }

    /**
     * Create a mock for a non-existing class
     *
     * @param string $className The name of the class to mock
     * @param array|null $methods The methods to mock (optional)
     * @return \PHPUnit\Framework\MockObject\MockObject The mock object
     */
    private function getMockForNonExistingClass(string $className, array $methods = null)
    {
        $builder = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->allowMockingUnknownTypes();

        if ($methods !== null) {
            $builder->setMethods($methods);
        }

        return $builder->getMock();
    }

    public function testProcessWebhookWithOrderReferenceAndExistingTransaction(): void
    {
        $webhookData = [
            'id' => 'webhook-123',
            'data' => [
                'paymentId' => 'payment-123',
                'order' => [
                    'reference' => '000000123'
                ]
            ]
        ];

        $paymentId = 'payment-123';
        $orderReference = '000000123';

        // Mock order and collection
        $orderMock = $this->createMock(Order::class);
        $orderCollectionMock = $this->createMock(OrderCollection::class);

        // Mock transaction
        $transactionMock = $this->createMock(TransactionInterface::class);

        // Setup expectations for getTransactionByPaymentId
        $this->webhookDataLoaderMock->expects($this->once())
            ->method('getTransactionByPaymentId')
            ->with($paymentId)
            ->willReturn($transactionMock);

        // Setup expectations for order collection
        $this->orderCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderCollectionMock);
        $orderCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('increment_id', $orderReference)
            ->willReturnSelf();
        $orderCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($orderMock);

        // Setup expectations for saveComment
        $this->commentMock->expects($this->once())
            ->method('saveComment')
            ->with(
                __('Webhook Received. Payment created for payment ID: %1', $paymentId),
                $orderMock
            );

        // Execute the method
        $this->paymentCreated->processWebhook($webhookData);
    }

    public function testProcessWebhookWithoutOrderReferenceAndExistingTransaction(): void
    {
        $webhookData = [
            'id' => 'webhook-123',
            'data' => [
                'paymentId' => 'payment-123'
            ]
        ];

        $paymentId = 'payment-123';
        $orderReference = '000000123';

        // Mock order, payment, and collections
        $orderMock = $this->createMock(Order::class);
        $paymentMock = $this->createMock(Payment::class);
        $paymentCollectionMock = $this->createMock(PaymentCollection::class);
        $orderCollectionMock = $this->createMock(OrderCollection::class);

        // Mock transaction
        $transactionMock = $this->createMock(TransactionInterface::class);

        // Setup expectations for getTransactionByPaymentId
        $this->webhookDataLoaderMock->expects($this->once())
            ->method('getTransactionByPaymentId')
            ->with($paymentId)
            ->willReturn($transactionMock);

        // Setup expectations for payment collection
        $this->paymentCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($paymentCollectionMock);
        $paymentCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('last_trans_id', $paymentId)
            ->willReturnSelf();
        $paymentCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($paymentMock);
        $paymentMock->expects($this->once())
            ->method('getParentId')
            ->willReturn(1);

        // Setup expectations for order collection
        $this->orderCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderCollectionMock);
        $orderCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('entity_id', 1)
            ->willReturnSelf();
        $orderCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($orderMock);

        // Setup expectations for order
        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn($orderReference);

        $orderMock->method('getId')->willReturn(1);

        // Setup expectations for saveComment
        $this->commentMock->expects($this->once())
            ->method('saveComment')
            ->with(
                __('Webhook Received. Payment created for payment ID: %1', $paymentId),
                $orderMock
            );

        // Execute the method
        $this->paymentCreated->processWebhook($webhookData);
    }

    public function testProcessWebhookWithOrderReferenceAndNoTransaction(): void
    {
        $webhookData = [
            'id' => 'webhook-123',
            'data' => [
                'paymentId' => 'payment-123',
                'order' => [
                    'reference' => '000000123'
                ]
            ]
        ];

        $paymentId = 'payment-123';
        $orderReference = '000000123';

        // Mock order, payment, and collections
        $orderMock = $this->createMock(Order::class);
        $paymentMock = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['addTransactionCommentsToOrder'])
            ->getMockForAbstractClass();
        $orderCollectionMock = $this->createMock(OrderCollection::class);

        // Mock transaction
        $transactionMock = $this->createMock(TransactionInterface::class);

        // Setup expectations for getTransactionByPaymentId
        $this->webhookDataLoaderMock->expects($this->once())
            ->method('getTransactionByPaymentId')
            ->with($paymentId)
            ->willReturn(null);

        // Setup expectations for order collection
        $this->orderCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderCollectionMock);
        $orderCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('increment_id', $orderReference)
            ->willReturnSelf();
        $orderCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($orderMock);

        // Setup expectations for saveComment
        $this->commentMock->expects($this->once())
            ->method('saveComment')
            ->with(
                __('Webhook Received. Payment created for payment ID: %1', $paymentId),
                $orderMock
            );

        // Setup expectations for order state
        $orderMock->expects($this->once())
            ->method('getState')
            ->willReturn(Order::STATE_NEW);
        $orderMock->expects($this->once())
            ->method('setState')
            ->with(Order::STATE_PENDING_PAYMENT)
            ->willReturnSelf();
        $orderMock->expects($this->once())
            ->method('setStatus')
            ->with(Order::STATE_PENDING_PAYMENT)
            ->willReturnSelf();

        // Setup expectations for payment
        $orderMock->expects($this->atLeastOnce())
            ->method('getPayment')
            ->willReturn($paymentMock);

        // Setup expectations for transaction builder
        $this->transactionBuilderMock->expects($this->once())
            ->method('build')
            ->with(
                $paymentId,
                $orderMock,
                ['payment_id' => $paymentId],
                TransactionInterface::TYPE_PAYMENT
            )
            ->willReturn($transactionMock);

        // Setup expectations for payment
        $paymentMock->expects($this->once())
            ->method('addTransactionCommentsToOrder')
            ->with(
                $transactionMock,
                $this->anything()
            );

        // Setup expectations for order save
        $this->orderRepositoryMock->expects($this->once())
            ->method('save')
            ->with($orderMock);

        // Execute the method
        $this->paymentCreated->processWebhook($webhookData);
    }

    public function testProcessWebhookWithNoTransactionAndOrderNotInNewState(): void
    {
        $webhookData = [
            'id' => 'webhook-123',
            'data' => [
                'paymentId' => 'payment-123',
                'order' => [
                    'reference' => '000000123'
                ]
            ]
        ];

        $paymentId = 'payment-123';
        $orderReference = '000000123';

        // Mock order and collection
        $orderMock = $this->createMock(Order::class);
        $orderCollectionMock = $this->createMock(OrderCollection::class);

        // Setup expectations for getTransactionByPaymentId
        $this->webhookDataLoaderMock->expects($this->once())
            ->method('getTransactionByPaymentId')
            ->with($paymentId)
            ->willReturn(null);

        // Setup expectations for order collection
        $this->orderCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderCollectionMock);
        $orderCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('increment_id', $orderReference)
            ->willReturnSelf();
        $orderCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($orderMock);

        // Setup expectations for saveComment
        $this->commentMock->expects($this->once())
            ->method('saveComment')
            ->with(
                __('Webhook Received. Payment created for payment ID: %1', $paymentId),
                $orderMock
            );

        // Setup expectations for order state
        $orderMock->expects($this->once())
            ->method('getState')
            ->willReturn(Order::STATE_PROCESSING);

        // No transaction should be created
        $this->transactionBuilderMock->expects($this->never())
            ->method('build');

        // Execute the method
        $this->paymentCreated->processWebhook($webhookData);
    }
}

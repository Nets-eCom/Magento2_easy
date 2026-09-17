<?php

namespace Nexi\Checkout\Test\Unit\Model\Webhook;

use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Nexi\Checkout\Gateway\AmountConverter;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use Nexi\Checkout\Model\Webhook\PaymentRefundCompleted;
use NexiCheckout\Model\Webhook\Data\Amount;
use NexiCheckout\Model\Webhook\Data\RefundCompletedData;
use NexiCheckout\Model\Webhook\RefundCompleted;
use NexiCheckout\Model\Webhook\WebhookInterface;
use PHPUnit\Framework\TestCase;

class PaymentRefundCompletedTest extends TestCase
{
    /**
     * @var WebhookDataLoader|\PHPUnit\Framework\MockObject\MockObject
     */
    private $webhookDataLoaderMock;

    /**
     * @var Builder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transactionBuilderMock;

    /**
     * @var OrderRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var CreditmemoFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $creditmemoFactoryMock;

    /**
     * @var CreditmemoManagementInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $creditmemoManagementMock;

    /**
     * @var AmountConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $amountConverterMock;

    /**
     * @var Comment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $commentMock;

    /**
     * @var WebhookInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $webhookMock;

    /**
     * @var RefundCompletedData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $refundCompletedDataMock;

    /**
     * @var Amount|\PHPUnit\Framework\MockObject\MockObject
     */
    private $amountMock;

    /**
     * @var PaymentRefundCompleted
     */
    private $paymentRefundCompleted;

    protected function setUp(): void
    {
        $this->webhookDataLoaderMock    = $this->createMock(WebhookDataLoader::class);
        $this->transactionBuilderMock   = $this->createMock(Builder::class);
        $this->orderRepositoryMock      = $this->createMock(OrderRepositoryInterface::class);
        $this->creditmemoFactoryMock    = $this->createMock(CreditmemoFactory::class);
        $this->creditmemoManagementMock = $this->createMock(CreditmemoManagementInterface::class);
        $this->amountConverterMock      = $this->createMock(AmountConverter::class);
        $this->commentMock              = $this->createMock(Comment::class);
        $this->webhookMock              = $this->createMock(RefundCompleted::class);
        $this->refundCompletedDataMock  = $this->createMock(RefundCompletedData::class);
        $this->amountMock               = $this->createMock(Amount::class);

        $this->paymentRefundCompleted = new PaymentRefundCompleted(
            $this->webhookDataLoaderMock,
            $this->transactionBuilderMock,
            $this->orderRepositoryMock,
            $this->creditmemoFactoryMock,
            $this->creditmemoManagementMock,
            $this->amountConverterMock,
            $this->commentMock
        );
    }

    public function testProcessWebhookWithFullRefund(): void
    {
        $paymentId = 'payment-123';
        $refundId  = 'refund-123';
        $amountValue = 10000; // 100.00 in cents
        $currency = 'USD';

        // Mock amount
        $this->amountMock->expects($this->any())
            ->method('getAmount')
            ->willReturn($amountValue);
        $this->amountMock->expects($this->any())
            ->method('getCurrency')
            ->willReturn($currency);

        // Mock refund completed data
        $this->refundCompletedDataMock->expects($this->any())
            ->method('getPaymentId')
            ->willReturn($paymentId);
        $this->refundCompletedDataMock->expects($this->any())
            ->method('getRefundId')
            ->willReturn($refundId);
        $this->refundCompletedDataMock->expects($this->any())
            ->method('getAmount')
            ->willReturn($this->amountMock);

        // Mock webhook
        $this->webhookMock->expects($this->any())
            ->method('getData')
            ->willReturn($this->refundCompletedDataMock);

        // Mock order and payment
        $orderMock      = $this->createMock(Order::class);
        $orderMock->method('canCreditmemo')->willReturn(true);
        $paymentMock    = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['addTransactionCommentsToOrder', 'addTransaction'])
            ->getMockForAbstractClass();
        $creditmemoMock = $this->createMock(Creditmemo::class);

        // Mock transaction
        $refundTransactionMock = $this->createMock(TransactionInterface::class);

        // Setup expectations for loadOrderByPaymentId
        $this->webhookDataLoaderMock->expects($this->once())
            ->method('loadOrderByPaymentId')
            ->with($paymentId)
            ->willReturn($orderMock);

        // Setup expectations for transaction builder
        $this->transactionBuilderMock->expects($this->once())
            ->method('build')
            ->with(
                $refundId,
                $orderMock,
                ['payment_id' => $paymentId],
                TransactionInterface::TYPE_REFUND
            )
            ->willReturn($refundTransactionMock);

        // Setup expectations for transaction
        $refundTransactionMock->expects($this->once())
            ->method('setParentTxnId')
            ->with($paymentId)
            ->willReturnSelf();
        $refundTransactionMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with('details', $this->anything())
            ->willReturnSelf();

        // Setup expectations for isFullRefund
        $orderMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn(100.00);
        $this->amountConverterMock->expects($this->once())
            ->method('convertToNexiAmount')
            ->with(100.00)
            ->willReturn(10000);

        // Setup expectations for processFullRefund
        $this->creditmemoFactoryMock->expects($this->once())
            ->method('createByOrder')
            ->with($orderMock)
            ->willReturn($creditmemoMock);
        $creditmemoMock->expects($this->once())
            ->method('setTransactionId')
            ->with($refundId)
            ->willReturnSelf();
        $this->creditmemoManagementMock->expects($this->once())
            ->method('refund')
            ->with($creditmemoMock);

        // Setup expectations for payment
        $orderMock->expects($this->atLeastOnce())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $orderMock->expects($this->atLeastOnce())
            ->method('getTotalRefunded')
            ->willReturn(0.00);

        // Setup expectations for order save
        $this->orderRepositoryMock->expects($this->once())
            ->method('save')
            ->with($orderMock);

        // Execute the method
        $this->paymentRefundCompleted->processWebhook($this->webhookMock);
    }
}

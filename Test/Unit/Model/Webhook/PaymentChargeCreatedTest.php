<?php

declare(strict_types=1);

namespace Nexi\Checkout\Test\Unit\Model\Webhook;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Nexi\Checkout\Gateway\AmountConverter;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use Nexi\Checkout\Model\Webhook\PaymentChargeCreated;
use Nexi\Checkout\Setup\Patch\Data\AddPaymentAuthorizedOrderStatus;
use PHPUnit\Framework\TestCase;

class PaymentChargeCreatedTest extends TestCase
{
    /**
     * @var OrderRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var WebhookDataLoader|\PHPUnit\Framework\MockObject\MockObject
     */
    private $webhookDataLoaderMock;

    /**
     * @var Builder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transactionBuilderMock;

    /**
     * @var Comment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $commentMock;

    /**
     * @var PaymentChargeCreated
     */
    private $paymentChargeCreated;

    /**
     * @var AmountConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $amountConverterMock;

    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->webhookDataLoaderMock = $this->createMock(WebhookDataLoader::class);
        $this->transactionBuilderMock = $this->createMock(Builder::class);
        $this->commentMock = $this->createMock(Comment::class);
        $this->amountConverterMock = $this->createMock(AmountConverter::class);

        $this->paymentChargeCreated = new PaymentChargeCreated(
            $this->orderRepositoryMock,
            $this->webhookDataLoaderMock,
            $this->transactionBuilderMock,
            $this->commentMock,
            $this->amountConverterMock
        );
    }

    public function testProcessWebhookWithFullCharge(): void
    {
        $webhookData = [
            'id' => 'webhook-123',
            'data' => [
                'paymentId' => 'payment-123',
                'chargeId' => 'charge-123',
                'amount' => [
                    'amount' => 10000, // 100.00 in cents
                    'currency' => 'USD'
                ],
                'orderItems' => []
            ]
        ];

        $paymentId = 'payment-123';
        $chargeId = 'charge-123';

        // Mock order and payment
        $orderMock = $this->createMock(Order::class);
        $paymentMock = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['addTransactionCommentsToOrder'])
            ->getMockForAbstractClass();
        $invoiceMock = $this->createMock(Invoice::class);

        // Mock transactions
        $reservationTransactionMock = $this->createMock(TransactionInterface::class);
        $chargeTransactionMock = $this->createMock(TransactionInterface::class);

        // Setup expectations for loadOrderByPaymentId
        $this->webhookDataLoaderMock->expects($this->once())
            ->method('loadOrderByPaymentId')
            ->with($paymentId)
            ->willReturn($orderMock);

        // Setup expectations for saveComment
        $this->commentMock->expects($this->once())
            ->method('saveComment')
            ->with(
                __(
                    'Webhook Received. Payment charge created for payment ID: %1<br/>Charge ID: %2<br/>Amount: %3 %4.',
                    $paymentId,
                    $chargeId,
                    number_format($webhookData['data']['amount']['amount'] / 100, 2, '.', ''),
                    $webhookData['data']['amount']['currency']
                ),
                $orderMock
            );

        // Setup expectations for getTransactionByOrderId
        $this->webhookDataLoaderMock->expects($this->once())
            ->method('getTransactionByOrderId')
            ->with($this->anything(), TransactionInterface::TYPE_AUTH)
            ->willReturn($reservationTransactionMock);

        // Setup expectations for getTransactionByPaymentId
        $this->webhookDataLoaderMock->expects($this->once())
            ->method('getTransactionByPaymentId')
            ->with($chargeId, TransactionInterface::TYPE_CAPTURE)
            ->willReturn(null);

        // Setup expectations for transaction builder
        $this->transactionBuilderMock->expects($this->once())
            ->method('build')
            ->with(
                $chargeId,
                $orderMock,
                $this->anything(),
                TransactionInterface::TYPE_CAPTURE
            )
            ->willReturn($chargeTransactionMock);

        // Setup expectations for transaction
        $reservationTransactionMock->expects($this->once())
            ->method('getTransactionId')
            ->willReturn('txn-123');
        $reservationTransactionMock->expects($this->once())
            ->method('getTxnId')
            ->willReturn('txn-123');
        $chargeTransactionMock->expects($this->once())
            ->method('setParentId')
            ->with('txn-123')
            ->willReturnSelf();
        $chargeTransactionMock->expects($this->once())
            ->method('setParentTxnId')
            ->with('txn-123')
            ->willReturnSelf();

        // Setup expectations for isFullCharge
        $orderMock->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn(100.00);

        // Setup expectations for order state update
        $orderMock->expects($this->once())
            ->method('setState')
            ->with(Order::STATE_PROCESSING)
            ->willReturnSelf();
        $orderMock->expects($this->once())
            ->method('setStatus')
            ->with(Order::STATE_PROCESSING)
            ->willReturnSelf();

        // Setup expectations for order save
        $this->orderRepositoryMock->expects($this->once())
            ->method('save')
            ->with($orderMock);

        // Execute the method
        $this->paymentChargeCreated->processWebhook($webhookData);
    }
}

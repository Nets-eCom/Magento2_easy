<?php

namespace Nexi\Checkout\Test\Unit\Model\Webhook;

use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\SubscriptionManagement;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use Nexi\Checkout\Model\Webhook\PaymentReservationCreated;
use Nexi\Checkout\Setup\Patch\Data\AddPaymentAuthorizedOrderStatus;
use PHPUnit\Framework\TestCase;

class PaymentReservationCreatedTest extends TestCase
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
     * @var PaymentReservationCreated
     */
    private $paymentReservationCreated;

    /**
     * @var Comment
     */
    private Comment $commentMock;

    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->webhookDataLoaderMock = $this->createMock(WebhookDataLoader::class);
        $this->transactionBuilderMock = $this->createMock(Builder::class);
        $this->commentMock = $this->createMock(Comment::class);
        $this->subscriptionManagement = $this->createMock(SubscriptionManagement::class);

        $this->paymentReservationCreated = new PaymentReservationCreated(
            $this->orderRepositoryMock,
            $this->webhookDataLoaderMock,
            $this->transactionBuilderMock,
            $this->commentMock,
            $this->subscriptionManagement
        );
    }

    public function testProcessWebhookSuccessfully(): void
    {
        $webhookData = [
            'id' => 'webhook-123',
            'data' => [
                'paymentId' => 'payment-123',
                'amount' => [
                    'amount' => 1300,
                ]
            ]
        ];

        $paymentId = 'payment-123';
        $rawAmount = 1300;
        $formattedAmount = 13.00;

        // Mock order and payment
        $orderMock = $this->createMock(Order::class);
        $paymentMock = $this->createMock(Payment::class);

        // Mock reservation transaction
        $reservationTransactionMock =  $this->getMockBuilder(TransactionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrder'])
            ->getMockForAbstractClass();

        // Setup expectations
        $this->webhookDataLoaderMock
            ->method('getTransactionByPaymentId')
            ->willReturnMap([
                [$webhookData['id'], TransactionInterface::TYPE_AUTH, null],
                [$paymentId, TransactionInterface::TYPE_PAYMENT, $reservationTransactionMock]
            ]);

        $reservationTransactionMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('setState')
            ->with(Order::STATE_PENDING_PAYMENT)
            ->willReturnSelf();

        $orderMock->expects($this->once())
            ->method('setStatus')
            ->with(AddPaymentAuthorizedOrderStatus::STATUS_NEXI_AUTHORIZED)
            ->willReturnSelf();

        $this->transactionBuilderMock->expects($this->once())
            ->method('build')
            ->with(
                $webhookData['id'],
                $orderMock,
                ['payment_id' => $paymentId],
                TransactionInterface::TYPE_AUTH
            )
            ->willReturn($reservationTransactionMock);

        $reservationTransactionMock->expects($this->once())
            ->method('setIsClosed')
            ->with(0)
            ->willReturnSelf();

        $reservationTransactionMock->expects($this->once())
            ->method('setParentTxnId')
            ->with($paymentId)
            ->willReturnSelf();

        $reservationTransactionMock->expects($this->once())
            ->method('setParentId')
            ->willReturnSelf();

        $orderMock->method('getPayment')
            ->willReturn($paymentMock);

        $paymentMock->expects($this->once())
            ->method('formatAmount')
            ->with($rawAmount / 100, true)
            ->willReturn($formattedAmount);

        $paymentMock->expects($this->once())
            ->method('setBaseAmountAuthorized')
            ->with($formattedAmount)
            ->willReturnSelf();

        $this->orderRepositoryMock->expects($this->once())
            ->method('save')
            ->with($orderMock);

        // Execute the method
        $this->paymentReservationCreated->processWebhook($webhookData);
    }

    public function testProcessWebhookThrowsExceptionWhenTransactionNotFound(): void
    {
        $webhookData = [
            'id' => 'webhook-123',
            'data' => [
                'paymentId' => 'payment-123'
            ]
        ];

        $this->webhookDataLoaderMock
            ->method('getTransactionByPaymentId')
            ->with($webhookData['data']['paymentId'], TransactionInterface::TYPE_PAYMENT)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Payment transaction not found for payment-123.');

        // Execute the method
        $this->paymentReservationCreated->processWebhook($webhookData);
    }
}

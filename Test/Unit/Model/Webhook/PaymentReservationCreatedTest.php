<?php
declare(strict_types=1);

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
use NexiCheckout\Model\Webhook\Data\Amount;
use NexiCheckout\Model\Webhook\Data\ReservationCreatedData;
use NexiCheckout\Model\Webhook\ReservationCreated;
use NexiCheckout\Model\Webhook\WebhookInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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
     * @var Comment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $commentMock;

    /**
     * @var SubscriptionManagement|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subscriptionManagement;

    /**
     * @var WebhookInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $webhookMock;

    /**
     * @var ReservationCreatedData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $reservationCreatedDataMock;

    /**
     * @var Amount|\PHPUnit\Framework\MockObject\MockObject
     */
    private $amountMock;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->webhookDataLoaderMock = $this->createMock(WebhookDataLoader::class);
        $this->transactionBuilderMock = $this->createMock(Builder::class);
        $this->commentMock = $this->createMock(Comment::class);
        $this->subscriptionManagement = $this->createMock(SubscriptionManagement::class);
        $this->webhookMock = $this->createMock(ReservationCreated::class);
        $this->reservationCreatedDataMock = $this->createMock(ReservationCreatedData::class);
        $this->amountMock = $this->createMock(Amount::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->paymentReservationCreated = new PaymentReservationCreated(
            $this->orderRepositoryMock,
            $this->webhookDataLoaderMock,
            $this->transactionBuilderMock,
            $this->commentMock,
            $this->subscriptionManagement,
            $this->loggerMock
        );
    }

    public function testProcessWebhookSuccessfully(): void
    {
        $webhookId = 'webhook-123';
        $paymentId = 'payment-123';
        $rawAmount = 1300;
        $formattedAmount = 13.00;
        $currency = 'EUR';

        // Mock amount
        $this->amountMock->expects($this->any())
            ->method('getAmount')
            ->willReturn($rawAmount);
        $this->amountMock->expects($this->any())
            ->method('getCurrency')
            ->willReturn($currency);

        // Mock reservation created data
        $this->reservationCreatedDataMock->expects($this->any())
            ->method('getPaymentId')
            ->willReturn($paymentId);
        $this->reservationCreatedDataMock->expects($this->any())
            ->method('getAmount')
            ->willReturn($this->amountMock);
        $this->reservationCreatedDataMock->expects($this->any())
            ->method('getPaymentMethod')
            ->willReturn('card');
        $this->reservationCreatedDataMock->expects($this->any())
            ->method('getPaymentType')
            ->willReturn('VISA');

        // Mock webhook
        $this->webhookMock->expects($this->any())
            ->method('getId')
            ->willReturn($webhookId);
        $this->webhookMock->expects($this->any())
            ->method('getData')
            ->willReturn($this->reservationCreatedDataMock);

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
                [$webhookId, TransactionInterface::TYPE_AUTH, null],
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
                $webhookId,
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
        $this->paymentReservationCreated->processWebhook($this->webhookMock);
    }

    public function testProcessWebhookThrowsExceptionWhenTransactionNotFound(): void
    {
        $webhookId = 'webhook-123';
        $paymentId = 'payment-123';

        // Mock reservation created data
        $this->reservationCreatedDataMock->expects($this->any())
            ->method('getPaymentId')
            ->willReturn($paymentId);

        // Mock webhook
        $this->webhookMock->expects($this->any())
            ->method('getId')
            ->willReturn($webhookId);
        $this->webhookMock->expects($this->any())
            ->method('getData')
            ->willReturn($this->reservationCreatedDataMock);

        $this->webhookDataLoaderMock
            ->method('getTransactionByPaymentId')
            ->with($paymentId, TransactionInterface::TYPE_PAYMENT)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Payment transaction not found for payment-123.');

        // Execute the method
        $this->paymentReservationCreated->processWebhook($this->webhookMock);
    }
}

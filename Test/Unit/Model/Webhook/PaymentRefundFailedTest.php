<?php

namespace Nexi\Checkout\Test\Unit\Model\Webhook;

use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use Nexi\Checkout\Model\Webhook\PaymentRefundFailed;
use NexiCheckout\Model\Webhook\Data\RefundFailedData;
use NexiCheckout\Model\Webhook\RefundFailed;
use NexiCheckout\Model\Webhook\WebhookInterface;
use PHPUnit\Framework\TestCase;

class PaymentRefundFailedTest extends TestCase
{
    /**
     * @var WebhookDataLoader|\PHPUnit\Framework\MockObject\MockObject
     */
    private $webhookDataLoaderMock;

    /**
     * @var Comment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $commentMock;

    /**
     * @var WebhookInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $webhookMock;

    /**
     * @var RefundFailedData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $refundFailedDataMock;

    /**
     * @var PaymentRefundFailed
     */
    private $paymentRefundFailed;

    protected function setUp(): void
    {
        $this->webhookDataLoaderMock = $this->createMock(WebhookDataLoader::class);
        $this->commentMock = $this->createMock(Comment::class);
        $this->webhookMock = $this->createMock(RefundFailed::class);
        $this->refundFailedDataMock = $this->createMock(RefundFailedData::class);

        $this->paymentRefundFailed = new PaymentRefundFailed(
            $this->webhookDataLoaderMock,
            $this->commentMock
        );
    }

    public function testProcessWebhookSuccessfully(): void
    {
        $paymentId = 'payment-123';

        // Mock webhook data
        $this->refundFailedDataMock->expects($this->once())
            ->method('getPaymentId')
            ->willReturn($paymentId);

        // Mock webhook
        $this->webhookMock->expects($this->once())
            ->method('getData')
            ->willReturn($this->refundFailedDataMock);

        // Mock order
        $orderMock = $this->createMock(Order::class);

        // Setup expectations
        $this->webhookDataLoaderMock->expects($this->once())
            ->method('loadOrderByPaymentId')
            ->with($paymentId)
            ->willReturn($orderMock);

        $this->commentMock->expects($this->once())
            ->method('saveComment')
            ->with(
                __('Webhook Received. Payment refund failed for payment ID: %1', $paymentId),
                $orderMock
            );

        // Execute the method
        $this->paymentRefundFailed->processWebhook($this->webhookMock);
    }
}

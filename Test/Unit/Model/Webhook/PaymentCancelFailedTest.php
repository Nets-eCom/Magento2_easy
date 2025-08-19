<?php

namespace Nexi\Checkout\Test\Unit\Model\Webhook;

use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use Nexi\Checkout\Model\Webhook\PaymentCancelFailed;
use NexiCheckout\Model\Webhook\Data\CancelFailedData;
use NexiCheckout\Model\Webhook\WebhookInterface;
use PHPUnit\Framework\TestCase;

class PaymentCancelFailedTest extends TestCase
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
     * @var CancelFailedData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cancelFailedDataMock;

    /**
     * @var PaymentCancelFailed
     */
    private $paymentCancelFailed;

    protected function setUp(): void
    {
        $this->webhookDataLoaderMock = $this->createMock(WebhookDataLoader::class);
        $this->commentMock = $this->createMock(Comment::class);
        $this->webhookMock = $this->createMock(WebhookInterface::class);
        $this->cancelFailedDataMock = $this->createMock(CancelFailedData::class);

        $this->paymentCancelFailed = new PaymentCancelFailed(
            $this->webhookDataLoaderMock,
            $this->commentMock
        );
    }

    public function testProcessWebhookSuccessfully(): void
    {
        $paymentId = 'payment-123';

        // Mock webhook data
        $this->cancelFailedDataMock->expects($this->exactly(2))
            ->method('getPaymentId')
            ->willReturn($paymentId);

        // Mock webhook
        $this->webhookMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturn($this->cancelFailedDataMock);

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
                __('Webhook Received. Payment cancel failed for payment ID: %1', $paymentId),
                $orderMock
            );

        // Execute the method
        $this->paymentCancelFailed->processWebhook($this->webhookMock);
    }
}

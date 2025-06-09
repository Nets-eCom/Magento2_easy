<?php

namespace Nexi\Checkout\Test\Unit\Model\Webhook;

use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use Nexi\Checkout\Model\Webhook\PaymentCancelCreated;
use PHPUnit\Framework\TestCase;

class PaymentCancelCreatedTest extends TestCase
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
     * @var PaymentCancelCreated
     */
    private $paymentCancelCreated;

    protected function setUp(): void
    {
        $this->webhookDataLoaderMock = $this->createMock(WebhookDataLoader::class);
        $this->commentMock = $this->createMock(Comment::class);

        $this->paymentCancelCreated = new PaymentCancelCreated(
            $this->webhookDataLoaderMock,
            $this->commentMock
        );
    }

    public function testProcessWebhookSuccessfully(): void
    {
        $webhookData = [
            'id' => 'webhook-123',
            'data' => [
                'paymentId' => 'payment-123'
            ]
        ];

        $paymentId = 'payment-123';

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
                __('Webhook Received. Payment cancel created for payment ID: %1', $paymentId),
                $orderMock
            );

        // Execute the method
        $this->paymentCancelCreated->processWebhook($webhookData);
    }
}

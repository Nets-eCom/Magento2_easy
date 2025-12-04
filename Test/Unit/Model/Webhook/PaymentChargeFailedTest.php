<?php

namespace Nexi\Checkout\Test\Unit\Model\Webhook;

use Magento\Sales\Model\Order;
use Nexi\Checkout\Model\Order\Comment;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;
use Nexi\Checkout\Model\Webhook\PaymentChargeFailed;
use NexiCheckout\Model\Webhook\Data\ChargeFailedData;
use NexiCheckout\Model\Webhook\WebhookInterface;
use PHPUnit\Framework\TestCase;

class PaymentChargeFailedTest extends TestCase
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
     * @var ChargeFailedData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $chargeFailedDataMock;

    /**
     * @var PaymentChargeFailed
     */
    private $paymentChargeFailed;

    protected function setUp(): void
    {
        $this->webhookDataLoaderMock = $this->createMock(WebhookDataLoader::class);
        $this->commentMock = $this->createMock(Comment::class);
        $this->webhookMock = $this->createMock(WebhookInterface::class);
        $this->chargeFailedDataMock = $this->createMock(ChargeFailedData::class);

        $this->paymentChargeFailed = new PaymentChargeFailed(
            $this->webhookDataLoaderMock,
            $this->commentMock
        );
    }

    public function testProcessWebhookSuccessfully(): void
    {
        $paymentId = 'payment-123';

        // Mock webhook data
        $this->chargeFailedDataMock->expects($this->once())
            ->method('getPaymentId')
            ->willReturn($paymentId);

        // Mock webhook
        $this->webhookMock->expects($this->once())
            ->method('getData')
            ->willReturn($this->chargeFailedDataMock);

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
                __('Webhook Received. Payment charge failed for payment ID: %1', $paymentId),
                $orderMock
            );

        // Execute the method
        $this->paymentChargeFailed->processWebhook($this->webhookMock);
    }
}

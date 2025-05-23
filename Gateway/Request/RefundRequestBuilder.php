<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Request\NexiCheckout\SalesDocumentItemsBuilder;
use NexiCheckout\Model\Request\Charge\Shipping;
use NexiCheckout\Model\Request\PartialRefundCharge;

class RefundRequestBuilder implements BuilderInterface
{
    /**
     * @param SalesDocumentItemsBuilder $documentItemsBuilder
     */
    public function __construct(
        private readonly SalesDocumentItemsBuilder $documentItemsBuilder
    ) {
    }

    /**
     * Build nexi PartialRefundCharge request
     *
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject): array
    {
        /** @var Order $order */
        $payment    = $buildSubject['payment']->getPayment();
        $creditmemo = $payment->getCreditmemo();

        return [
            'nexi_method' => 'refundCharge',
            'body'        => [
                'chargeId' => $payment->getRefundTransactionId(),
                'refund'     => new PartialRefundCharge(
                    orderItems: $this->documentItemsBuilder->build($creditmemo),
                    myReference: $creditmemo->getIncrementId(),
                )
            ]
        ];
    }
}

<?php

namespace Nexi\Checkout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Request\NexiCheckout\SalesDocumentItemsBuilder;
use NexiCheckout\Model\Request\Charge\Shipping;
use NexiCheckout\Model\Request\PartialRefundCharge;

class RefundRequestBuilder implements BuilderInterface
{
    public function __construct(
        private readonly SalesDocumentItemsBuilder $documentItemsBuilder
    ) {
    }

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

    /**
     * @param Order $order
     *
     * @return Shipping|null
     */
    private function getShipping(Order $order)
    {
        if ($order->getShipmentsCollection()->getSize() > 0) {
            /** @var Order\Shipment $shipping */
            $shipping = $order->getShipmentsCollection()->getLastItem();
            return new Shipping(
                $shipping->getTracksCollection()->getLastItem()->getTrackNumber(),
                $shipping->getTracksCollection()->getLastItem()->getCarrierCode()
            );
        }

        return null;
    }

}

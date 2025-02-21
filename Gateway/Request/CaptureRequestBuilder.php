<?php

namespace Nexi\Checkout\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Request\NexiCheckout\SalesDocumentItemsBuilder;
use NexiCheckout\Model\Request\Charge\Shipping;
use NexiCheckout\Model\Request\PartialCharge;

class CaptureRequestBuilder implements BuilderInterface
{
    public function __construct(
        private readonly SalesDocumentItemsBuilder $documentItemsBuilder
    ) {
    }

    public function build(array $buildSubject): array
    {
        /** @var Order\Payment $payment */
        $payment    = SubjectReader::readPayment($buildSubject)->getPayment();

        $invoice = $payment->getOrder()->getInvoiceCollection()->getLastItem();

        return [
            'nexi_method' => 'charge',
            'body'        => [
                'paymentId' => $payment->getAdditionalInformation('payment_id'),
                'charge'     => new PartialCharge(
                    $this->documentItemsBuilder->build($invoice),
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

<?php

namespace Nexi\Checkout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Request\NexiCheckout\RequestFactory;
use NexiCheckout\Model\Request\Charge\Shipping;
use NexiCheckout\Model\Request\FullCharge;

class CaptureRequestBuilder implements BuilderInterface
{
    public function __construct(
        private readonly RequestFactory $requestFactory
    ) {
    }

    public function build(array $buildSubject): array
    {
        /** @var Order $order */
        $order = $buildSubject['payment']->getPayment()->getOrder();

        return [
            'nexi_method' => 'charge',
            'body'        => [
                'payment_id' => $order->getPayment()->getAdditionalInformation('payment_id'),
                'charge'     => new FullCharge(
                    amount  : (int)($order->getGrandTotal() * 100),
                    shipping: $this->getShipping($order)
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

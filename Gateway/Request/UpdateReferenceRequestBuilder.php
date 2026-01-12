<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use NexiCheckout\Model\Request\ReferenceInformation;

class UpdateReferenceRequestBuilder implements BuilderInterface
{
    /**
     * @param UrlInterface $url
     */
    public function __construct(
        private readonly UrlInterface $url
    ) {
    }

    /**
     * Build the request for updating the reference - order Increment ID.
     *
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject): array
    {
        /** @var Order $order */
        $order = $buildSubject['order'];
        $payment = $order->getPayment();
        $incrementId = $order->getIncrementId();

        return [
            'nexi_method' => 'updateReferenceInformation',
            'body' => [
                'paymentId' => $payment->getAdditionalInformation('payment_id'),
                'referenceInformation' => new ReferenceInformation(
                    checkoutUrl: $this->url->getUrl('checkout/onepage/success'),
                    reference: $incrementId,
                )
            ]
        ];
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Request\NexiCheckout\SalesDocumentItemsBuilder;
use NexiCheckout\Model\Request\PartialCharge;

class CaptureRequestBuilder implements BuilderInterface
{
    /**
     * Constructor
     *
     * @param SalesDocumentItemsBuilder $documentItemsBuilder
     */
    public function __construct(
        private readonly SalesDocumentItemsBuilder $documentItemsBuilder
    ) {
    }

    /**
     * Build nexi PartialCharge request
     *
     * @param array $buildSubject
     *
     * @return array
     */
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
}

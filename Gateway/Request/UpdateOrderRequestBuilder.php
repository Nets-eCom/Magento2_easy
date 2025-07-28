<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\AmountConverter;
use NexiCheckout\Model\Request\UpdateOrder;

class UpdateOrderRequestBuilder implements BuilderInterface
{
    /**
     * @param CreatePaymentRequestBuilder $createPaymentRequestBuilder
     * @param AmountConverter $amountConverter
     */
    public function __construct(
        private readonly CreatePaymentRequestBuilder $createPaymentRequestBuilder,
        private readonly AmountConverter $amountConverter
    ) {
    }

    /**
     * Build the request for updating the payment order.
     *
     * @param array $buildSubject
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function build(array $buildSubject): array
    {
        /** @var Order|Quote $paymentSubject */
        $paymentSubject = $buildSubject['payment']?->getPayment()?->getOrder();

        if (!$paymentSubject) {
            $paymentSubject = $buildSubject['payment']?->getPayment()?->getQuote();
        }

        if (!$paymentSubject) {
            $paymentSubject = $buildSubject['payment']?->getQuote();
        }

        return [
            'nexi_method' => 'updatePaymentOrder',
            'body'        => [
                'paymentId'    => $paymentSubject->getPayment()->getAdditionalInformation('payment_id'),
                'updateOrder' => new UpdateOrder(
                    amount        : $this->amountConverter->convertToNexiAmount($paymentSubject->getGrandTotal()),
                    items         : $this->createPaymentRequestBuilder->buildItems($paymentSubject),
                    shipping      : new UpdateOrder\Shipping(costSpecified: true),
                    paymentMethods: [],
                )
            ]
        ];
    }
}

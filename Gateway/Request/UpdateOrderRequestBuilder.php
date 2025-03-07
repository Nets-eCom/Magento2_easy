<?php

namespace Nexi\Checkout\Gateway\Request;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use NexiCheckout\Model\Request\UpdateOrder;

class UpdateOrderRequestBuilder implements BuilderInterface
{
    /**
     * @param CreatePaymentRequestBuilder $createPaymentRequestBuilder
     */
    public function __construct(
        private readonly CreatePaymentRequestBuilder $createPaymentRequestBuilder,
    ) {
    }

    /**
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
                    amount        : (int)($paymentSubject->getGrandTotal() * 100),
                    items         : $this->createPaymentRequestBuilder->buildItems($paymentSubject),
                    shipping      : new UpdateOrder\Shipping(costSpecified: true),
                    paymentMethods: [],
                )
            ]
        ];
    }
}

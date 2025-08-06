<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Request\NexiCheckout\GlobalRequestBuilder;
use Nexi\Checkout\Model\Subscription\SubscriptionLinkRepository;
use NexiCheckout\Model\Request\BulkChargeSubscription;
use NexiCheckout\Model\Request\BulkChargeSubscription\Subscription;
use NexiCheckout\Model\Request\Shared\Notification;

class SubscriptionChargeRequestBuilder implements BuilderInterface
{
    /**
     * SubscriptionChargeRequestBuilder constructor.
     *
     * @param SubscriptionLinkRepository $subscriptionLinkRepository
     * @param GlobalRequestBuilder $globalRequestBuilder
     */
    public function __construct(
        private readonly SubscriptionLinkRepository $subscriptionLinkRepository,
        private readonly GlobalRequestBuilder $globalRequestBuilder
    ) {
    }

    /**
     * Build the request for subscription charge.
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var Order $paymentSubject */
        $paymentSubject = $buildSubject['order'];

        if (!$paymentSubject) {
            $paymentSubject = $buildSubject['order']->getPayment()->getQuote();
        }

        $subscription = $this->subscriptionLinkRepository->getSubscriptionFromOrderId($paymentSubject->getId());

        return [
            'body' => new BulkChargeSubscription(
                externalBulkChargeId: 'bulkChargeId_' . $paymentSubject->getIncrementId(),
                notification: new Notification($this->globalRequestBuilder->buildWebhooks()),
                subscriptions: [
                    new Subscription(
                        subscriptionId: $subscription->getNexiSubscriptionId(),
                        externalReference: $paymentSubject->getIncrementId(),
                        order: $this->globalRequestBuilder->buildOrder($paymentSubject)
                    )
                ]
            ),
            'nexi_method' => 'bulkChargeSubscription',
        ];
    }
}

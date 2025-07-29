<?php

namespace Nexi\Checkout\Model\Api;

use Magento\Sales\Api\OrderRepositoryInterface;
use Nexi\Checkout\Api\SubscriptionLinkRepositoryInterface;

class ShowSubscriptionsDataProvider
{
    /**
     * @var SubscriptionLinkRepositoryInterface
     */
    private $subscriptionLinkRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * ShowSubscriptionsDataProvider constructor.
     *
     * @param SubscriptionLinkRepositoryInterface $subscriptionLinkRepository
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        SubscriptionLinkRepositoryInterface $subscriptionLinkRepository,
        OrderRepositoryInterface $orderRepository,
    ) {
        $this->subscriptionLinkRepository = $subscriptionLinkRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param $subscriptionId
     * @return array
     */
    public function getOrderDataFromSubscriptionId($subscriptionId)
    {
        $orderIds = array_last($this->subscriptionLinkRepository->getOrderIdsBySubscriptionId($subscriptionId));
        $order = $this->orderRepository->get($orderIds);

        return [
            'increment_id' => $order->getIncrementId(),
            'grand_total' => $order->getGrandTotal()
        ];
    }
}

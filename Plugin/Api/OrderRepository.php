<?php
namespace Nexi\Checkout\Plugin\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Nexi\Checkout\Api\Data\SubscriptionInterface;

class OrderRepository
{
    /** @var \Nexi\Checkout\Api\SubscriptionRepositoryInterface */
    private $subscriptionRepository;

    /** @var \Magento\Sales\Api\Data\OrderExtensionFactory */
    private $orderExtensionFactory;

    /** @var \Nexi\Checkout\Model\Subscription\SubscriptionLinkRepository */
    private $subscriptionLinkRepository;

    /**
     * @param \Nexi\Checkout\Api\SubscriptionRepositoryInterface $subscriptionRepository
     * @param \Magento\Sales\Api\Data\OrderExtensionFactory $extensionFactory
     * @param \Nexi\Checkout\Model\Subscription\SubscriptionLinkRepository $subscriptionLinkRepository
     */
    public function __construct(
        \Nexi\Checkout\Api\SubscriptionRepositoryInterface               $subscriptionRepository,
        \Magento\Sales\Api\Data\OrderExtensionFactory                  $extensionFactory,
        \Nexi\Checkout\Model\Subscription\SubscriptionLinkRepository $subscriptionLinkRepository
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->orderExtensionFactory = $extensionFactory;
        $this->subscriptionLinkRepository = $subscriptionLinkRepository;
    }

    /**
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function afterSave(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        $order
    ) {
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes && $extensionAttributes->getRecurringPayment()) {
            $extensionAttributes->getRecurringPayment()->setOrderId($order->getId());
            $this->subscriptionRepository->save($extensionAttributes->getRecurringPayment());
        }

        return $order;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return SubscriptionInterface|bool
     */
    private function getRecurringPayment(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        try {
            $payment = $this->subscriptionLinkRepository->getSubscriptionFromOrderId($order->getId());
        } catch (NoSuchEntityException $e) {
            $payment = false;
        }

        return $payment;
    }
}

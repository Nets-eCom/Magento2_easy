<?php

namespace Nexi\Checkout\Model\Subscription;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Vault\Model\ResourceModel\PaymentToken;
use Nexi\Checkout\Api\Data\SubscriptionInterface;
use Nexi\Checkout\Api\Data\SubscriptionInterfaceFactory;
use Nexi\Checkout\Api\SubscriptionRepositoryInterface;

class SubscriptionCreate
{
    private const SCHEDULED_ATTRIBUTE_CODE = 'subscription_schedule';
    private const REPEAT_COUNT_STATIC_VALUE = 5;

    /**
     * @var SubscriptionRepositoryInterface
     */
    private $subscriptionRepository;

    /**
     * @var SubscriptionInterfaceFactory
     */
    private $subscriptionInterfaceFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepositoryInterface;

    /**
     * @var NextDateCalculator
     */
    private $dateCalculator;

    /**
     * @var PaymentToken
     */
    private $paymentToken;

    /**
     * @var SubscriptionLinkRepository
     */
    private $subscriptionLinkRepository;

    /**
     * @param SubscriptionRepositoryInterface $subscriptionRepository
     * @param SubscriptionInterfaceFactory $subscriptionInterfaceFactory
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param \Nexi\Checkout\Model\Subscription\NextDateCalculator $dateCalculator
     * @param PaymentToken $paymentToken
     * @param SubscriptionLinkRepository $subscriptionLinkRepository
     */
    public function __construct(
        SubscriptionRepositoryInterface $subscriptionRepository,
        SubscriptionInterfaceFactory    $subscriptionInterfaceFactory,
        ProductRepositoryInterface      $productRepositoryInterface,
        NextDateCalculator              $dateCalculator,
        PaymentToken                    $paymentToken,
        SubscriptionLinkRepository      $subscriptionLinkRepository
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->subscriptionInterfaceFactory = $subscriptionInterfaceFactory;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->dateCalculator = $dateCalculator;
        $this->paymentToken = $paymentToken;
        $this->subscriptionLinkRepository = $subscriptionLinkRepository;
    }

    /**
     * @param $order
     * @return SubscriptionInterface
     * @throws CouldNotSaveException
     */
    public function createSubscription($order): SubscriptionInterface
    {
        try {
            $subscription = $this->subscriptionInterfaceFactory->create();
            $subscription->setStatus(SubscriptionInterface::STATUS_PENDING_PAYMENT);
            $subscription->setCustomerId($order->getCustomerId());
            $subscription->setNextOrderDate($this->dateCalculator->getNextDate(1));
            $subscription->setRecurringProfileId(1);
            $subscription->setRepeatCountLeft(self::REPEAT_COUNT_STATIC_VALUE);
            $subscription->setRetryCount(self::REPEAT_COUNT_STATIC_VALUE);
            $subscription->setSelectedToken(1);

            $this->subscriptionRepository->save($subscription);

            $this->subscriptionLinkRepository->linkOrderToSubscription($order->getId(), $subscription->getId());

            return $subscription;
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
    }

    /**
     * @param $order
     * @return array
     */
    public function getSubscriptionSchedule($order): array
    {
        $orderSchedule = [];
        try {
            foreach ($order->getItems() as $item) {
                $product = $this->productRepositoryInterface->getById($item->getProductId());
                if (is_object($product->getCustomAttribute(self::SCHEDULED_ATTRIBUTE_CODE))) {
                    if ($product->getCustomAttribute(self::SCHEDULED_ATTRIBUTE_CODE)->getValue() >= 0) {
                        $orderSchedule[] = $product->getCustomAttribute(self::SCHEDULED_ATTRIBUTE_CODE)->getValue();
                    }
                }
            }
        } catch (NoSuchEntityException $e) {
            return [];
        }

        return $orderSchedule;
    }
}

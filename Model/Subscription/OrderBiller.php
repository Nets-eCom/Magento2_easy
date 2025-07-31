<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Subscription;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandManagerPool;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Service\InvoiceService;
use Nexi\Checkout\Api\SubscriptionLinkRepositoryInterface;
use Nexi\Checkout\Api\SubscriptionRepositoryInterface;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\ResourceModel\Subscription as SubscriptionResource;
use Nexi\Checkout\Model\ResourceModel\Subscription\CollectionFactory;
use Nexi\Checkout\Model\Subscription;
use Psr\Log\LoggerInterface;
use Nexi\Checkout\Model\ResourceModel\Subscription\Collection;

class OrderBiller
{
    /**
     * OrderBiller constructor.
     *
     * @param PaymentCount $paymentCount
     * @param CollectionFactory $collectionFactory
     * @param NextDateCalculator $nextDateCalculator
     * @param SubscriptionRepositoryInterface $subscriptionRepository
     * @param SubscriptionResource $subscriptionResource
     * @param LoggerInterface $logger
     * @param SubscriptionLinkRepositoryInterface $subscriptionLinkRepository
     * @param OrderSender $orderSender
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        private PaymentCount                                                           $paymentCount,
        private CollectionFactory                                                      $collectionFactory,
        private NextDateCalculator                                                     $nextDateCalculator,
        private SubscriptionRepositoryInterface                                        $subscriptionRepository,
        private SubscriptionResource                                                   $subscriptionResource,
        private LoggerInterface                                                        $logger,
        private SubscriptionLinkRepositoryInterface                                    $subscriptionLinkRepository,
        private OrderSender                                                            $orderSender,
        private OrderRepository                                                        $orderRepository,
        private CommandManagerPool $commandManagerPool
    ) {
    }

    /**
     * Bill orders by ID.
     *
     * @param int[] $orderIds
     *
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function billOrdersById($orderIds)
    {
        foreach ($orderIds as $orderId) {
            $paymentSuccess = $this->chargeSubscription($orderId);
            if (!$paymentSuccess) {
                /** @var Subscription $subscription */
                $subscription = $this->subscriptionLinkRepository->getSubscriptionFromOrderId($orderId);
                $this->paymentCount->reduceFailureRetryCount($subscription);
                continue;
            }
            /** @var Collection $subscriptionsToCharge */
            $subscriptionsToCharge = $this->collectionFactory->create();
            $subscriptionsToCharge->getBillingCollectionByOrderIds($orderIds);
            $this->sendOrderConfirmationEmail($subscription->getId());
            $this->updateNextOrderDate($subscription);
        }
    }

    /**
     * Send order confirmation email.
     *
     * @param string $subscriptionId
     *
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendOrderConfirmationEmail($subscriptionId)
    {
        $orderIds = $this->subscriptionLinkRepository->getOrderIdsBySubscriptionId($subscriptionId);
        $this->orderSender->send($this->orderRepository->get(array_last($orderIds)));
    }

    /**
     * Create MIT payment request.
     *
     * @param string $orderId
     *
     * @return bool
     * For subscription param @see Collection::getBillingCollectionByOrderIds
     */
    private function chargeSubscription($orderId): bool
    {
        $paymentSuccess = false;
        try {
            $order           = $this->orderRepository->get($orderId);
            $commandExecutor = $this->commandManagerPool->get(Config::CODE);

            $commandExecutor->executeByCode(
                commandCode: 'subscription_charge',
                arguments  : ['order' => $order]
            );
        } catch (LocalizedException $e) {
            $this->logger->error(
                \__(
                    "Subscription: Unable to create a charge to customer's subscription error: %error",
                    ['error' => $e->getMessage()]
                )
            );
        }

        return $paymentSuccess;
    }

    /**
     * Update next order date.
     *
     * @param Subscription $subscription
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function updateNextOrderDate(Subscription $subscription)
    {
        $subscription->setNextOrderDate(
            $this->nextDateCalculator->getNextDate(
                $subscription->getRecurringProfileId(),
                $subscription->getNextOrderDate()
            )->format('Y-m-d')
        );

        $this->saveSubscription($subscription);
    }

    /**
     * Save subscription.
     *
     * @param Subscription $subscription
     *
     * @return void
     */
    private function saveSubscription(Subscription $subscription): void
    {
        try {
            $this->subscriptionRepository->save($subscription);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical(
                \__(
                    'Recurring payment:
                    Cancelling subscription %id, unable to update subscription\'s next order date: %error',
                    [
                        'id' => $subscription->getId(),
                        'error' => $e->getMessage()
                    ]
                )
            );

            // Prevent subscription from being rebilled over and over again
            // if for some reason the subscription is unable to be saved.
            $this->subscriptionResource->forceFailedStatus($subscription->getId());
        }
    }
}

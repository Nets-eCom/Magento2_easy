<?php

namespace Nexi\Checkout\Model\Subscription;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Service\InvoiceService;
use Nexi\Checkout\Api\SubscriptionLinkRepositoryInterface;
use Nexi\Checkout\Api\SubscriptionRepositoryInterface;
use Nexi\Checkout\Model\ResourceModel\Subscription as SubscriptionResource;
use Nexi\Checkout\Model\ResourceModel\Subscription\CollectionFactory;
use Nexi\Checkout\Model\Subscription;
use Nexi\Checkout\Model\Token\Payment;
use Psr\Log\LoggerInterface;
use \Nexi\Checkout\Model\ResourceModel\Subscription\Collection;

class OrderBiller
{
    /**
     * OrderBiller constructor.
     *
     * @param PaymentCount $paymentCount
     * @param Payment $mitPayment
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
        private PaymentCount                        $paymentCount,
        private Payment                             $mitPayment,
        private CollectionFactory                   $collectionFactory,
        private NextDateCalculator                  $nextDateCalculator,
        private SubscriptionRepositoryInterface     $subscriptionRepository,
        private SubscriptionResource                $subscriptionResource,
        private LoggerInterface                     $logger,
        private SubscriptionLinkRepositoryInterface $subscriptionLinkRepository,
        private OrderSender                         $orderSender,
        private OrderRepository                     $orderRepository,
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
        /** @var Collection $subscriptionsToCharge */
        $subscriptionsToCharge = $this->collectionFactory->create();
        $subscriptionsToCharge->getBillingCollectionByOrderIds($orderIds);

        /** @var Subscription $subscription */
        foreach ($subscriptionsToCharge as $subscription) {
            if (!$this->validateToken($subscription)) {
                continue;
            }

            $paymentSuccess = $this->createMitPayment($subscription);
            if (!$paymentSuccess) {
                $this->paymentCount->reduceFailureRetryCount($subscription);
                continue;
            }
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
     * Validate token.
     *
     * @param Subscription $subscription
     *
     * @return bool
     */
    private function validateToken($subscription)
    {
        $valid = true;
        if (!$subscription->getData('token_active') || !$subscription->getData('token_visible')) {
            $this->logger->warning(
                \__(
                    'Unable to charge subscription id: %id token is invalid',
                    ['id' => $subscription->getId()]
                )
            );
            $this->paymentCount->reduceFailureRetryCount($subscription);

            $valid = false;
        }

        return $valid;
    }

    /**
     * Create MIT payment request.
     *
     * @param Subscription $subscription Must include order id of the subscription and public hash of the vault token.
     *
     * @return bool
     * For subscription param @see Collection::getBillingCollectionByOrderIds
     */
    private function createMitPayment($subscription): bool
    {
        $paymentSuccess = false;
        try {
            $paymentSuccess = $this->mitPayment->makeMitPayment(
                $subscription->getData('order_id'),
                $subscription->getData('token')
            );
        } catch (LocalizedException $e) {
            $this->logger->error(
                \__(
                    'Recurring Payment: Unable to create a charge to customer token error: %error',
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
            )
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
                        'id'    => $subscription->getId(),
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

<?php

namespace Nexi\Checkout\Model\Subscription;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Nexi\Checkout\Api\Data\SubscriptionInterface;
use Nexi\Checkout\Model\Subscription\Email;
use Nexi\Checkout\Model\Subscription\OrderCloner;
use Nexi\Checkout\Model\Subscription\SubscriptionLinkRepository;
use Nexi\Checkout\Model\ResourceModel\Subscription;
use Psr\Log\LoggerInterface;

class Notify
{
    private const ARRAY_INDEX_ZERO = 0;

    /**
     * @var OrderCloner
     */
    private $orderCloner;

    /**
     * @var Subscription
     */
    private $subscriptionResource;

    /**
     * @var Email
     */
    private $email;

    /**
     * @var SubscriptionLinkRepository
     */
    private                 $subscriptionLinkRepository;

    private LoggerInterface $logger;

    /**
     * @param OrderCloner $orderCloner
     * @param Subscription $subscriptionResource
     * @param Email $email
     * @param SubscriptionLinkRepository $subscriptionLinkRepository
     */
    public function __construct(
        OrderCloner                $orderCloner,
        Subscription               $subscriptionResource,
        Email                      $email,
        SubscriptionLinkRepository $subscriptionLinkRepository,
        LoggerInterface            $logger
    ) {
        $this->orderCloner = $orderCloner;
        $this->subscriptionResource = $subscriptionResource;
        $this->email = $email;
        $this->subscriptionLinkRepository = $subscriptionLinkRepository;
        $this->logger = $logger;
    }

    /**
     * Clones recurring payments that are due in the next payment period and notifies customer's whos orders were
     * cloned.
     *
     * @return void
     */
    public function process()
    {
        $validIds = $this->getValidOrderIds();
        if (empty($validIds)) {
            return;
        }

        $clonedOrders = $this->orderCloner->cloneOrders($validIds);
        $i = self::ARRAY_INDEX_ZERO;
        $validIds = array_values($validIds);

        if (count($validIds) === count($clonedOrders)) {
            foreach ($clonedOrders as $clonedOrder) {
                $this->subscriptionLinkRepository->linkOrderToSubscription(
                    $clonedOrder->getId(),
                    $this->subscriptionLinkRepository->getSubscriptionIdFromOrderId($validIds[$i])
                );
                $i++;
            }
        }

        $this->email->sendNotifications($clonedOrders);
    }

    private function getValidOrderIds()
    {
        try {
            return $this->subscriptionResource->getClonableOrderIds();
        } catch (LocalizedException $e) {
            $this->logger->error(\__(
                'Recurring Payment unable to fetch clonable order ids: %error',
                ['error' => $e->getMessage()]
            ));

            return [];
        }
    }
}

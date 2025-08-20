<?php
declare(strict_types=1);

namespace Nexi\Checkout\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Nexi\Checkout\Model\Subscription\TotalConfigProvider;
use Nexi\Checkout\Plugin\PreventDifferentScheduledCart;

class ScheduledCartValidation implements ObserverInterface
{
    /**
     * ScheduledCartValidation constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param TotalConfigProvider $totalConfigProvider
     * @param Session $customerSession
     */
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private TotalConfigProvider     $totalConfigProvider,
        private Session                 $customerSession
    ) {
    }

    /**
     * Execute.
     *
     * @param Observer $observer
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $cartSchedule = null;
        $cartId = $observer->getEvent()->getOrder()->getQuoteId();
        $cart = $this->cartRepository->get($cartId);

        if ($cart->getItems() && $this->totalConfigProvider->isSubscriptionsEnabled()) {
            foreach ($cart->getItems() as $cartItem) {
                $cartItemSchedule = $cartItem
                    ->getProduct()
                    ->getCustomAttribute(PreventDifferentScheduledCart::SCHEDULE_CODE);

                if (!$this->customerSession->isLoggedIn() && $cartItemSchedule) {
                    throw new LocalizedException(__("Can't place order with scheduled products when not logged in"));
                }

                if ($cartItemSchedule && $cartItemSchedule->getValue()) {
                    if (null !== $cartSchedule && $cartSchedule !== $cartItemSchedule->getValue()) {
                        throw new LocalizedException(__("Can't place order with different scheduled products in cart"));
                    } else {
                        $cartSchedule = $cartItemSchedule->getValue();
                    }
                }
            }
        }
    }
}

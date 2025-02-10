<?php

namespace Nexi\Checkout\Model;

use Magento\Checkout\Model\Session;
use Nexi\Checkout\Api\RedirectInterface;

class Redirect implements RedirectInterface
{

    public function __construct(
        private readonly Session $checkoutSession
    )
    {
    }

    public function guestRedirect(string $cartId, string $email, string $paymentMethod, string $billingAddress): string
    {
        $redirectUrl = null;
        try {
           $order = $this->checkoutSession->getLastRealOrder();
            $payment = $order->getPayment();
            $additionalData = $payment->getAdditionalInformation();

            if (isset($additionalData['redirect_url'])) {
                $redirectUrl = $additionalData['redirect_url'];
            }
        } catch (LocalizedException|NoSuchEntityException $e) {
            $this->logger->error($e->getMessage() . ' ' . $e->getTraceAsString());
        }

        return $redirectUrl;
    }

    public function customerRedirect(string $cartId, string $paymentMethod, string $billingAddress): string
    {
        // TODO: Implement customerRedirect() method.
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Plugin;

use Magento\Checkout\Model\GuestPaymentInformationManagement;
use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Checkout\Model\Session;

class PaymentInformationManagementPlugin
{
    /**
     * @param Session $checkoutSession
     */
    public function __construct(
        private readonly Session $checkoutSession,
    ) {
    }

    /**
     * Add redirect URL to the response after placing an order.
     *
     * @param PaymentInformationManagement|GuestPaymentInformationManagement $subject
     * @param false|mixed|string $result
     *
     * @return false|mixed|string
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        $subject,
        $result
    ) {
        $redirectUrl = $this->getRedirectUrl();

        if ($redirectUrl) {
            $result = json_encode(['result' => $result, 'redirect_url' => $redirectUrl]);
        }

        return $result;
    }

    /**
     * Get the redirect URL from the order payment information.
     *
     * @return string[]
     */
    private function getRedirectUrl()
    {
        $order   = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation('redirect_url');
    }
}

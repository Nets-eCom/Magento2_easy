<?php

declare(strict_types=1);

namespace Nexi\Checkout\Plugin;

use Magento\Checkout\Model\GuestPaymentInformationManagement;
use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Checkout\Model\Session;
use Nexi\Checkout\Model\Language;

class PaymentInformationManagementPlugin
{
    /**
     * @param Session $checkoutSession
     * @param Language $language
     */
    public function __construct(
        private readonly Session $checkoutSession,
        private readonly Language $language
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
     * @return string
     */
    private function getRedirectUrl()
    {
        $order   = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();

        $redirectUrl = $payment?->getAdditionalInformation('redirect_url');

        if ($redirectUrl && $this->language->getCode() !== Language::DEFAULT_LOCALE) {
            $separator = strpos((string)$redirectUrl, '?') !== false ? '&' : '?';
            $redirectUrl .= $separator . 'language=' . $this->language->getCode();
        }

        return $redirectUrl;
    }
}

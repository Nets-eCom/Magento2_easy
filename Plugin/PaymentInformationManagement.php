<?php

namespace Nexi\Checkout\Plugin;

use Exception;
use Magento\Checkout\Model\PaymentInformationManagement as Subject;
use Magento\Checkout\Model\Session;
use Psr\Log\LoggerInterface;

class PaymentInformationManagement
{

    /**
     * @param Session $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Session                  $checkoutSession,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Add redirect URL to the response after placing an order.
     *
     * @param Subject $subject
     * @param false|mixed|string $result
     *
     * @return false|mixed|string
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        Subject $subject,
        $result
    ) {
        try {
            $redirectUrl = $this->getRedirectUrl();

            if ($redirectUrl) {
                $result = json_encode(['result' => $result, 'redirect_url' => $redirectUrl]);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . ' ' . $e->getTraceAsString());
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

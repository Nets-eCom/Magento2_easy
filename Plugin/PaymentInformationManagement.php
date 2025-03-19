<?php

namespace Nexi\Checkout\Plugin;

use Exception;
use Magento\Checkout\Model\PaymentInformationManagement as Subject;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class PaymentInformationManagement
{

    public function __construct(
        private readonly Session                  $checkoutSession,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Subject $subject
     * @param $result
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
            $this->logger->error(
                $e->getMessage(),
                [
                    'trace' => $e->getTraceAsString()
                ]
            );
        }

        return $result;
    }

    /**
     * Get redirect URL from payment additional information
     */
    private function getRedirectUrl()
    {
        $order   = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation('redirect_url');
    }
}

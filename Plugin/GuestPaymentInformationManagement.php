<?php

namespace Nexi\Checkout\Plugin;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\GuestPaymentInformationManagement as Subject;
use Psr\Log\LoggerInterface;

class GuestPaymentInformationManagement
{

    public function __construct(
        private readonly Session         $checkoutSession,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Subject $subject
     * @param $result
     *
     * @return false|mixed|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
            $this->logger->error($e->getMessage() . ' GuestPaymentInformationManagement.php' . $e->getTraceAsString());
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function getRedirectUrl()
    {
        $order   = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation('redirect_url');
    }
}

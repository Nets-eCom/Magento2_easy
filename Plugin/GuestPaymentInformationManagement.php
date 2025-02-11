<?php

namespace Nexi\Checkout\Plugin;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Checkout\Model\GuestPaymentInformationManagement as Subject;

class GuestPaymentInformationManagement
{

    public function __construct(
        private readonly Session                  $checkoutSession,
        private readonly \Psr\Log\LoggerInterface $logger
    ) {
    }

    /**
     * @param GuestPaymentInformationManagement $subject
     * @param $result
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     */
    public function afterSavePaymentInformationAndPlaceOrder(Subject $subject, $result, $cartId, $email, PaymentInterface $paymentMethod, \Magento\Quote\Api\Data\AddressInterface $billingAddress = null)
    {
        try {
            $redirectUrl = $this->getRedirectUrl();

            if ($redirectUrl) {
                $result = json_encode(['result' => $result, 'redirect_url' => $redirectUrl]);
            }
        } catch (LocalizedException|NoSuchEntityException $e) {
            $this->logger->error($e->getMessage() . ' ' . $e->getTraceAsString());
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function getRedirectUrl()
    {
        $order          = $this->checkoutSession->getLastRealOrder();
        $payment        = $order->getPayment();

        return $payment->getAdditionalInformation('redirect_url');
    }
}

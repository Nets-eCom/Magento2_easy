<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\Token;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Nexi\Checkout\Logger\NexiLogger;
use Paytrail\SDK\Request\AbstractPaymentRequest;

class RequestData
{
    /**
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param NexiLogger $nexiLogger
     */
    public function __construct(
        private readonly OrderRepositoryInterface        $orderRepositoryInterface,
        private readonly PaymentTokenManagementInterface $paymentTokenManagement,
        private readonly NexiLogger                  $nexiLogger
    ) {
    }

    public function setTokenPaymentRequestData() {
        return true;
    }

    /**
     * Get payment token.
     *
     * @param string $tokenHash
     * @param string $customerId
     *
     * @return PaymentTokenInterface|null
     */
    private function getPaymentToken($tokenHash, $customerId): ?PaymentTokenInterface
    {
        return $this->paymentTokenManagement->getByPublicHash($tokenHash, $customerId);
    }
}

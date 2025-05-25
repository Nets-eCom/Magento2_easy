<?php

namespace Nexi\Checkout\Gateway\Validator;

use Nexi\Checkout\Logger\NexiLogger;
use Nexi\Checkout\Model\Adapter\Adapter;

class HmacValidator
{
    public const SKIP_HMAC_VALIDATION = 'skip_hmac';

    /**
     * HmacValidator constructor.
     *
     * @param NexiLogger $log
     * @param Adapter $nexiAdapter
     */
    public function __construct(
        private NexiLogger $log,
        private Adapter $nexiAdapter
    ) {
    }

    /**
     * Validate HMAC signature.
     *
     * @param array $params
     * @param string $signature
     * @return bool
     */
    public function validateHmac(array $params, string $signature): bool
    {
        try {
            $this->log->debugLog(
                'request',
                \sprintf(
                    'Validating Hmac for transaction: %s',
                    $params["checkout-transaction-id"]
                )
            );
            $nexiClient = $this->nexiAdapter->initNexiMerchantClient();

            $nexiClient->validateHmac($params, '', $signature);
        } catch (\Exception $e) {
            $this->log->error(sprintf(
                'Nexi PaymentService error: Hmac validation failed for transaction %s',
                $params["checkout-transaction-id"]
            ));

            return false;
        }
        $this->log->debugLog(
            'response',
            sprintf(
                'Hmac validation successful for transaction: %s',
                $params["checkout-transaction-id"]
            )
        );

        return true;
    }
}

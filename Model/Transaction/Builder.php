<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Transaction;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Nexi\Checkout\Gateway\Config\Config;

class Builder
{
    /**
     * @param BuilderInterface $transactionBuilder
     * @param Config $config
     */
    public function __construct(
        private readonly BuilderInterface $transactionBuilder,
        private readonly Config           $config
    ) {
    }

    /**
     * Build transaction
     *
     * @param string $transactionId
     * @param Order $order
     * @param mixed $transactionData
     * @param string $action
     *
     * @return TransactionInterface
     */
    public function build($transactionId, Order $order, $transactionData, $action): TransactionInterface
    {
        return $this->transactionBuilder->setOrder($order)
            ->setPayment($order->getPayment())
            ->setTransactionId($transactionId)
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => $transactionData]
            )
            ->setFailSafe(true)
            ->setMessage('Payment transaction - return action.')
            ->build($action ?: $this->config->getPaymentAction());
    }
}

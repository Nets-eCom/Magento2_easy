<?php

namespace Nexi\Checkout\Model\Transaction;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Nexi\Checkout\Gateway\Config\Config;

class Builder
{

    /**
     * Constructor
     *
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
     * @param Order $order
     * @param mixed $transactionData
     * @param null $action
     *
     * @return TransactionInterface
     */
    public function build(Order $order, $transactionData, $action ): TransactionInterface
    {
        return $this->transactionBuilder->setOrder($order)
            ->setPayment($order->getPayment())
            ->setTransactionId($order->getPayment()->getAdditionalInformation('payment_id'))
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => $transactionData]
            )
            ->setFailSafe(true)
            ->setMessage('Payment transaction - return action.')
            ->build($action ?: $this->config->getPaymentAction());
    }
}

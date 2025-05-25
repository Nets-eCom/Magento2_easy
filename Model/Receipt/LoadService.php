<?php

namespace Nexi\Checkout\Model\Receipt;

use Magento\Framework\Exception\InputException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Sales\Model\OrderFactory;
use Nexi\Checkout\Exceptions\CheckoutException;
use Nexi\Checkout\Logger\NexiLogger;

class LoadService
{
    /**
     * LoadService constructor.
     *
     * @param Repository $transactionRepository
     * @param OrderFactory $orderFactory
     * @param NexiLogger $nexiLogger
     */
    public function __construct(
        private Repository     $transactionRepository,
        private OrderFactory   $orderFactory,
        private NexiLogger $nexiLogger
    ) {
    }

    /**
     * LoadTransaction function
     *
     * @param string $transactionId
     * @param Order $currentOrder
     * @param string $orderId
     *
     * @return bool|mixed
     * @throws \Nexi\Checkout\Exceptions\CheckoutException
     */
    public function loadTransaction($transactionId, $currentOrder, $orderId)
    {
        try {
            $transaction = $this->transactionRepository->getByTransactionId(
                $transactionId,
                $currentOrder->getPayment()->getId(),
                $orderId
            );
        } catch (InputException $e) {
            $this->nexiLogger->logData(\Monolog\Logger::ERROR, $e->getMessage());
            throw new CheckoutException(__($e->getMessage()));
        }

        return $transaction;
    }

    /**
     * LoadOrder function
     *
     * @param string $orderIncrementalId
     *
     * @return Order
     * @throws CheckoutException
     */
    public function loadOrder($orderIncrementalId)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementalId);
        if (!$order->getId()) {
            $this->nexiLogger->logData(\Monolog\Logger::ERROR, 'Order not found');
            throw new CheckoutException(__('Order not found'));
        }
        return $order;
    }
}

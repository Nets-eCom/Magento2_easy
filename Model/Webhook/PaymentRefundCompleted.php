<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentRefundCompleted implements WebhookProcessorInterface
{
    public function __construct(
        private readonly WebhookDataLoader $webhookDataLoader,
        private readonly Builder $transactionBuilder,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CreditmemoFactory $creditmemoFactory,
        private readonly CreditmemoManagementInterface $creditmemoManagement
    ) {
    }

    /**
     * ProcessWebhook function for 'payment.refund.completed' event.
     *
     * @param $webhookData
     *
     * @return void
     * @throws LocalizedException
     */
    public function processWebhook($webhookData): void
    {
        $order = $this->webhookDataLoader->loadOrderByPaymentId($webhookData['data']['paymentId']);

        $refund = $this->transactionBuilder
            ->build(
                $webhookData['id'],
                $order,
                ['payment_id' => $webhookData['data']['paymentId']],
                TransactionInterface::TYPE_REFUND
            )->setParentTxnId($webhookData['data']['paymentId'])
            ->setAdditionalInformation('details', json_encode($webhookData));

        if ($this->isFullRefund($webhookData, $order)) {
            $this->processFullRefund($webhookData, $order);
        }

        $order->getPayment()->addTransactionCommentsToOrder(
            $refund,
            __('Payment refund created, amount: %1', $webhookData['data']['amount']['amount'] / 100)
        );

        $this->orderRepository->save($order);
    }


    /**
     * Create creditmemo for whole order
     *
     * @param array $webhookData
     * @param Order $order
     *
     * @return void
     */
    public function processFullRefund(array $webhookData, Order $order)
    {
        $creditmemo = $this->creditmemoFactory->createByOrder($order);
        $creditmemo->setTransactionId($webhookData['id']);

        $this->creditmemoManagement->refund($creditmemo);
    }

    /**
     * Amount check
     *
     * @param array $webhookData
     * @param Order $order
     *
     * @return bool
     */
    private function isFullRefund(array $webhookData, Order $order)
    {
        return $order->getGrandTotal() == $webhookData['data']['amount']['amount']/100;
    }

}

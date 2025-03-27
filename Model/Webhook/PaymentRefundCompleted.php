<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Webhook;


use Magento\Sales\Api\Data\TransactionInterface;
use Nexi\Checkout\Model\Transaction\Builder;
use Nexi\Checkout\Model\Webhook\Data\WebhookDataLoader;

class PaymentRefundCompleted
{
    public function __construct(
        private WebhookDataLoader $webhookDataLoader,
        private Builder $transactionBuilder
    ) {
    }

    public function processWebhook($response)
    {
        $params = json_decode('{"id":"b16aadd52c574dedb8a3242e94ba6261","merchantId":100065206,"timestamp":"2025-03-03T14:54:26.4382+00:00","event":"payment.refund.completed","data":{"refundId":"ae4a07ad84d349acb373f777d29cfa53","reconciliationReference":"RRhncQ0LJITpbvV4LW9FXDSVR","amount":{"amount":4700,"currency":"EUR"},"paymentId":"9d0f058350e84981a9502fd31d8a512f"}}', true);
        $order = $this->webhookDataLoader->loadOrderByPaymentId($params['data']['paymentId']);

        $chargeRefundTransaction = $this->transactionBuilder
            ->build(
                $params['data']['refundId'],
                $order,
                [
                    'payment_id' => $params['data']['paymentId']
                ],
                TransactionInterface::TYPE_REFUND
            )->setParentTxnId($params['data']['paymentId']);
    }
}

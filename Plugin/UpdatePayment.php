<?php

namespace Nexi\Checkout\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\ResourceModel\Quote;
use Nexi\Checkout\Gateway\Config\Config;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;

class UpdatePayment {

    public function __construct(
        private readonly Config $config,
        private readonly \Psr\Log\LoggerInterface $logger,
        private readonly \Magento\Payment\Gateway\Command\CommandManagerPoolInterface $commandManagerPool
    ) {
    }

    /**
     * After save quote
     *
     * @param Quote $subject
     * @param $result
     * @param AbstractModel $object
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function afterSave(Quote $subject, $result, AbstractModel $object): mixed
    {
        if (!$this->config->isEmbedded() || $object->getData('no_payment_update_flag')) {

            return $result;
        }

        //send information to the payment gateway
        $quote = $object;
        $payment = $quote->getPayment();
        $paymentMethod = $payment->getMethod();
        if ($paymentMethod !== Config::CODE) {
            return $result;
        }

        $paymentId = $payment->getAdditionalInformation('payment_id');
        if (!$paymentId) {
            return $result;
        }

        try {
            $commandPool = $this->commandManagerPool->get(Config::CODE);
            $result      = $commandPool->executeByCode(
                commandCode: 'update_order',
                arguments  : ['payment' => $payment,]
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new LocalizedException(__('An error occurred during the payment process. Please try again later.'));
        }

        return $result;
    }
}

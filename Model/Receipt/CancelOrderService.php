<?php

namespace Nexi\Checkout\Model\Receipt;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Exceptions\CheckoutException;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Logger\NexiLogger;

class CancelOrderService
{
    /**
     * @param Config $gatewayConfig
     * @param UrlInterface $backendUrl
     * @param ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param OrderManagementInterface $orderManagementInterface
     * @param NexiLogger $logger
     */
    public function __construct(
        private Config $gatewayConfig,
        private UrlInterface $backendUrl,
        private ScopeConfigInterface $scopeConfig,
        private TransportBuilder $transportBuilder,
        private OrderManagementInterface $orderManagementInterface,
        private NexiLogger $logger
    ) {
    }

    /**
     * NotifyCanceledOrder
     *
     * @param Order $currentOrder
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function notifyCanceledOrder($currentOrder)
    {
        try {
            if (filter_var($this->gatewayConfig->getNotificationEmail(), FILTER_VALIDATE_EMAIL)) {
                $transport = $this->transportBuilder
                    ->setTemplateIdentifier('restore_order_notification')
                    ->setTemplateOptions([
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
                    ])
                    ->setTemplateVars([
                        'order' => [
                            'increment' => $currentOrder->getIncrementId(),
                            'url' => $this->backendUrl->getUrl(
                                'sales/order/view',
                                ['order_id' => $currentOrder->getId()]
                            )
                        ]
                    ])
                    ->setFrom([
                        'name' => $this->scopeConfig->getValue('general/store_information/name') . ' - Magento',
                        'email' => $this->scopeConfig->getValue('trans_email/ident_general/email'),
                    ])->addTo([
                        $this->gatewayConfig->getNotificationEmail()
                    ])->getTransport();
                $transport->sendMessage();
            }
        } catch (\Exception $e) {
            $this->logger->logData(\Monolog\Logger::ERROR, $e->getMessage());
        }
    }

    /**
     * CancelOrderById function
     *
     * @param string $orderId
     * @return void
     * @throws CheckoutException
     */
    public function cancelOrderById($orderId): void
    {
        if ($this->gatewayConfig->getCancelOrderOnFailedPayment()) {
            try {
                $this->orderManagementInterface->cancel($orderId);
            } catch (\Exception $e) {
                $this->logger->critical(sprintf(
                    'Nexi exception during order cancel: %s,\n error trace: %s',
                    $e->getMessage(),
                    $e->getTraceAsString()
                ));

                // Mask and throw end-user friendly exception
                throw new CheckoutException(__(
                    'Error while cancelling order.
                    Please contact customer support with order id: %id to release discount coupons.',
                    [ 'id'=> $orderId ]
                ));
            }
        }
    }
}

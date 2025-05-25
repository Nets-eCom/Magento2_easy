<?php

namespace Nexi\Checkout\Model\Subscription;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;

class Email
{
    const XML_PATH_EMAIL_TEMPLATE = 'sales/recurring_payment/email_template';
    const XML_PATH_EMAIL_WARNING_PERIOD = 'sales/recurring_payment/warning_period';
    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $emulation;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var Renderer
     */
    private $addressRenderer;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        TransportBuilder $transportBuilder,
        \Magento\Store\Model\App\Emulation $emulation,
        PaymentHelper $paymentHelper,
        Renderer $addressRenderer,
        ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->emulation = $emulation;
        $this->paymentHelper = $paymentHelper;
        $this->addressRenderer = $addressRenderer;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @param Order[] $clonedOrders
     */
    public function sendNotifications(array $clonedOrders)
    {
        foreach ($clonedOrders as $order) {
            $this->notify($order);
        }
    }

    /**
     * @param Order $order
     */
    private function notify($order)
    {
        try {
            $transport = $this->transportBuilder->setTemplateIdentifier($this->getEmailTemplateId($order))
                ->setTemplateOptions($this->getTemplateOptions($order))
                ->setTemplateVars($this->prepareTemplateVars($order))
                ->setFromByScope(
                    'sales',
                    $order->getStoreId()
                )->addTo($order->getCustomerEmail())
                ->getTransport();

            $this->emulation->startEnvironmentEmulation($order->getStoreId());
            $transport->sendMessage();
            $this->emulation->stopEnvironmentEmulation();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     *
     * @param Order $order
     * @return string
     */
    private function getEmailTemplateId($order)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
    }

    /**
     * @param Order $order
     * @return string[]
     */
    private function prepareTemplateVars($order): array
    {
        return [
            'order' => $order,
            'order_id' => $order->getId(),
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'created_at_formatted' => $order->getCreatedAtFormatted(2),
            'warning_period' => $this->getWarningPeriod($order),
            'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'is_not_virtual' => $order->getIsNotVirtual(),
                'email_customer_note' => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel()
            ]
        ];
    }

    /**
     * Get payment info block as html
     *
     * @param Order $order
     * @return string
     */
    private function getPaymentHtml(Order $order)
    {
        return $order->getPayment()->getMethod();
    }

    /**
     * Render shipping address into html.
     *
     * @param Order $order
     * @return string|null
     */
    private function getFormattedShippingAddress($order)
    {
        return $order->getIsVirtual()
            ? null
            : $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }

    /**
     * Render billing address into html.
     *
     * @param Order $order
     * @return string|null
     */
    private function getFormattedBillingAddress($order)
    {
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }

    /**
     * @param Order $order
     * @return array
     */
    private function getTemplateOptions(Order $order): array
    {
        return [
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => $order->getStoreId(),
        ];
    }

    private function getWarningPeriod($order)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_WARNING_PERIOD,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
    }
}

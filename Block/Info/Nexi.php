<?php

declare(strict_types=1);

namespace Nexi\Checkout\Block\Info;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Block\Info;
use Magento\Sales\Api\OrderRepositoryInterface;
use Nexi\Checkout\Gateway\Config\Config;

class Nexi extends Info
{
    /**
     * @var string
     */
    protected $_template = 'Nexi_Checkout::info/checkout.phtml';
    public const SELECTED_PATMENT_METHOD = 'selected_payment_method';
    public const SELECTED_PATMENT_TYPE = 'selected_payment_type';

    /**
     * Nexi constructor.
     *
     * @param Template\Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param Config $gatewayConfig
     * @param array $data
     */
    public function __construct(
        Template\Context                 $context,
        private OrderRepositoryInterface $orderRepository,
        private Config $gatewayConfig,
        array                            $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get logo to be displayed in the payment method block.
     *
     * @return mixed|null
     */
    public function getLogo()
    {
        return $this->gatewayConfig->getNexiLogo();
    }

    /**
     * Get the title to be displayed in the payment method block.
     *
     * @return mixed|null
     */
    public function getTitle()
    {
        return $this->gatewayConfig->getNexiTitle();
    }

    /**
     * Get payment selected method data.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getSelectedPaymentMethodData(): array
    {
        try {
            $payment = $this->orderRepository->get($this->getInfo()->getOrder()->getId())->getPayment();

            return [
                self::SELECTED_PATMENT_METHOD => $payment->getAdditionalInformation(self::SELECTED_PATMENT_METHOD),
                self::SELECTED_PATMENT_TYPE => $payment->getAdditionalInformation(self::SELECTED_PATMENT_TYPE),
            ];
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
        }

        return [];
    }
}

<?php

namespace Nexi\Checkout\Block\Info;

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

    /**
     * Nexi constructor.
     *
     * @param Template\Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        Template\Context                 $context,
        private OrderRepositoryInterface $orderRepository,
        array                            $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get Nexi logo.
     *
     * @return mixed
     */
    public function getNexiLogo()
    {
        return $this->_scopeConfig->getValue(
            'payment/nexi/logo',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get payment method title.
     *
     * @return mixed
     */
    public function getPaymentMethodTitle()
    {
        return $this->_scopeConfig->getValue(
            'payment/nexi/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get payment selected method.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentSelectedMethod(): string
    {
        $order = $this->orderRepository->get($this->getInfo()->getOrder()->getId());

        return $order->getPayment()->getAdditionalInformation(self::SELECTED_PATMENT_METHOD);
    }
}

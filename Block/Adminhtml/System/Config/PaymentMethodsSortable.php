<?php

declare(strict_types=1);

namespace Nexi\Checkout\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Nexi\Checkout\Model\Config\Source\PayTypeOptions;

class PaymentMethodsSortable extends Field
{
    protected $_template = 'Nexi_Checkout::system/config/payment-methods-sortable.phtml';

    /**
     * @param Context $context
     * @param PayTypeOptions $payTypeOptions
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context $context,
        private readonly PayTypeOptions $payTypeOptions,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
    }

    /**
     * Get HTML for the element
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        
        return $this->_toHtml();
    }

    /**
     * @return array{
     *  value: string,
     *  label: string,
     *  enabled: bool,
     *  position: int,
     * }
     */
    public function getSortedPaymentMethods(): array
    {
        $sorted = [];
        $savedValues = $this->getSelectedPaymentMethods();
        $availableMethods = $this->getAvailablePaymentMethods();

        foreach ($availableMethods as $index => $method) {
            $sorted[] = [
                'value' => $method['value'],
                'label' => $method['label'],
                'enabled' => in_array($method['value'], $savedValues),
                'position' => in_array($method['value'], $savedValues) 
                    ? array_search($method['value'], $savedValues) 
                    : count($savedValues) + $index
            ];
        }

        usort($sorted, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return $sorted;
    }

    /**
     * @return string[]
     */
    private function getSelectedPaymentMethods(): array
    {
        $element = $this->getElement();
        $value = $element->getValue();

        if (empty($value)) {
            return [];
        }

        return explode(',', (string)$value);
    }


    /**
     * Get all available payment methods from api
     *
     * @return array{
     *  value: string,
     *  label: string
     * }
     */
    private function getAvailablePaymentMethods(): array
    {
        return $this->payTypeOptions->toOptionArray();
    }
}

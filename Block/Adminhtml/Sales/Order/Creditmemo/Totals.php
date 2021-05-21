<?php


namespace Dibs\EasyCheckout\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\DataObject;

class Totals extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    protected $_dibsHelper;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $_currency;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Dibs\EasyCheckout\Helper\Data $dibsHelper,
        \Magento\Directory\Model\Currency $currency,
        array $data = []
    ) {
        
        $this->_dibsHelper = $dibsHelper;
        $this->_currency = $currency;
        
        parent::__construct($context, $data);
    }


    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->_currency->getCurrencySymbol();
    }

    /**
     * @return $this
     */
    public function initTotals()
    {
        if(!$this->getSource()->getDibsInvoiceFee()) {
            return $this;
        }
        
        $total = new DataObject([
            'code' => 'dibs_invoice_fee',
            'value' => $this->getSource()->getDibsInvoiceFee(),
            'label' => $this->_dibsHelper->getInvoiceFeeLabel(),
        ]);

        // add it to totals!
        $this->getParentBlock()->addTotalBefore($total, 'grand_total');
        return $this;
    }
    
}
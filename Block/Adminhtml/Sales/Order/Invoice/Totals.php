<?php


namespace Dibs\EasyCheckout\Block\Adminhtml\Sales\Order\Invoice;


class Totals extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    protected $_dibsHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Dibs\EasyCheckout\Helper\Data $dibsHelper,
        array $data = []
    ) {
        
        $this->_dibsHelper = $dibsHelper;
        
        parent::__construct($context, $data);
    }


    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * @return $this
     */
    public function initTotals()
    {
        if(!$this->getSource()->getDibsInvoiceFee()) {
            return $this;
        }
        
        $total = new \Magento\Framework\DataObject([
            'code' => 'dibs_invoice_fee',
            'value' => $this->getSource()->getDibsInvoiceFee(),
            'label' => $this->_dibsHelper->getInvoiceFeeLabel(),
        ]);

        // add it to totals!
        $this->getParentBlock()->addTotalBefore($total, 'grand_total');
        return $this;
    }

}
<?php
namespace Dibs\EasyCheckout\Block\Checkout;

use Magento\Catalog\Block as CatalogBlock;

/**
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Shortcut extends \Magento\Framework\View\Element\Template implements CatalogBlock\ShortcutInterface
{
    /**
     * Whether the block should be eventually rendered
     *
     * @var bool
     */
    protected $_shouldRender = true;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $_mathRandom;

    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    protected $helper;



    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Math\Random $mathRandom,
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession = null,
        array $data = []
    ) {

        $this->_checkoutSession = $checkoutSession;
        $this->_mathRandom = $mathRandom;
         $this->helper = $helper;
        $this->setTemplate('checkout/shortcut.phtml');

        parent::__construct($context, $data);
    }


    protected function _beforeToHtml()
    {
        $result = parent::_beforeToHtml();
       
        $isInCatalog = $this->getIsInCatalogProduct();
        $this->helper->logInfo($isInCatalog);
        
        if($isInCatalog) {
            $this->_shouldRender = false;
            return $result;
        }


        $quote = !$this->_checkoutSession ? null : $this->_checkoutSession->getQuote();

        // set misc data
        $this->setShortcutHtmlId(
            $this->_mathRandom->getUniqueHash('ec_shortcut_')
        )->setCheckoutUrl(
            $this->helper->getCheckoutUrl()
        );

     
        return $result;
    }

    /**
     * Render the block if needed
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_shouldRender) {
            return '';
        }
        return parent::_toHtml();
    }

    /**
     * Check is "OR" label position before shortcut
     *
     * @return bool
     */
    public function isOrPositionBefore()
    {

        return $this->getShowOrPosition() == CatalogBlock\ShortcutButtons::POSITION_BEFORE;
    }

    /**
     * Check is "OR" label position after shortcut
     *
     * @return bool
     */
    public function isOrPositionAfter()
    {
        return $this->getShowOrPosition() == CatalogBlock\ShortcutButtons::POSITION_AFTER;
    }
    
    public function getAlias() {
    
        return 'product.info.addtocart.dibs-easy-checkout';
    }


}

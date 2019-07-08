<?php

namespace Dibs\EasyCheckout\Block;

class Messages extends \Magento\Framework\View\Element\Messages
{

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addMessages($this->messageManager->getMessages(true));
        return parent::_prepareLayout();
    }
    
  
}

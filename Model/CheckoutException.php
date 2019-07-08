<?php


namespace Dibs\EasyCheckout\Model;


class CheckoutException extends \Magento\Framework\Exception\LocalizedException {

    protected $_redirect = null;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Phrase $phrase
     * @param \Exception $cause
     */
    public function __construct(\Magento\Framework\Phrase $phrase, $redirect = null, \Exception $cause = null)
    {
        $this->_redirect = $redirect;
        parent::__construct($phrase, $cause);
    }


    public function getRedirect() {
        return $this->_redirect;
    }

    public function isReload() {
        return $this->_redirect == '*/*';
    }


}
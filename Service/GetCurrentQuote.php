<?php


namespace Dibs\EasyCheckout\Service;


class GetCurrentQuote
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    protected $currentQuote;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    public function getQuote()
    {
        if ($this->currentQuote === null) {
            return $this->checkoutSession->getQuote();
        }

        return $this->currentQuote;
    }
}
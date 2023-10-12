<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Helper\Data;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;


class CartRevoke implements ActionInterface
{
    private Context $context;
    private Session $session;
    private CartRepositoryInterface $cartRepository;
    private RedirectFactory $redirectFactory;
    private Data $data;

    public function __construct(
        Context                 $context,
        Session                 $session,
        CartRepositoryInterface $cartRepository,
        RedirectFactory         $redirectFactory,
        Data    $data)
    {
        $this->context = $context;
        $this->session = $session;
        $this->cartRepository = $cartRepository;
        $this->redirectFactory = $redirectFactory;
        $this->data = $data;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function execute(): ResultInterface
    {
        $session = $this->session;

        $quote = $this->cartRepository->get($session->getQuoteId());
        $quote->setIsActive(true)->setReservedOrderId(null);

        //Mismatch interface but is bug from Magento 2
        $session->replaceQuote($quote);
        $this->cartRepository->save($quote);

        $redirect = $this->redirectFactory->create();

        return $redirect->setUrl($this->data->getCancelUrl() ?? '/checkout/cart/');
    }
}

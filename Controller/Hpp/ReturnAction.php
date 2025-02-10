<?php

namespace Nexi\Checkout\Controller\Hpp;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use NexiCheckout\Factory\PaymentApiFactory;

class ReturnAction implements ActionInterface
{
    public function __construct(
        private readonly RedirectFactory $resultRedirectFactory,
        private readonly RequestInterface $request,
        private readonly UrlInterface $url,
        private readonly Session $checkoutSession
    ) {
    }

    public function execute(): ResultInterface
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if ($order->getPayment()->getAdditionalInformation('payment_id') != $this->request->getParam('paymentid')) {
            throw new LocalizedException(__('Payment ID does not match.'));
        }



        $result = ['success' => false, 'errorMessage' => ''];
        $options = $this->request->getParams();



        return $this->resultRedirectFactory->create()->setUrl(
            $this->url->getUrl('checkout/onepage/success', ['_secure' => true])
        );
    }
}

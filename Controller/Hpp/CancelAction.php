<?php

namespace Nexi\Checkout\Controller\Hpp;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Nexi\Checkout\Model\Config\Source\Environment;
use NexiCheckout\Factory\PaymentApiFactory;

class CancelAction implements ActionInterface
{
    public function __construct(
        private readonly RedirectFactory $resultRedirectFactory,
        private readonly PaymentApiFactory $paymentApiFactory,
        private readonly RequestInterface $request,
        private readonly UrlInterface $url
    ) {
    }

    public function execute(): ResultInterface
    {
        $result = ['success' => false, 'errorMessage' => ''];
        $options = $this->request->getParams();

        try {
            $api = $this->paymentApiFactory->create(
                secretKey: $options['api_key'],
                isLiveMode: $options['environment'] == Environment::LIVE
            );

            $result = $api->retrievePayment($options['payment_id']);
        } catch (LocalizedException $e) {
            $result['errorMessage'] = $e->getMessage();
        }

        return $this->resultRedirectFactory->create()->setUrl(
            $this->url->getUrl('checkout/cart/index', ['_secure' => true])
        );
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filter\StripTags;
use Magento\Framework\Url;
use Magento\Store\Model\ScopeInterface;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Config\Source\Environment;
use NexiCheckout\Api\Exception\PaymentApiException;
use NexiCheckout\Factory\PaymentApiFactory;
use NexiCheckout\Model\Request\Item;
use NexiCheckout\Model\Request\Payment;

class TestConnection extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    public const  ADMIN_RESOURCE      = 'Magento_Catalog::config_catalog';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param StripTags $tagFilter
     * @param PaymentApiFactory $paymentApiFactory
     * @param Config $config
     * @param Url $url
     */
    public function __construct(
        Context                            $context,
        private readonly JsonFactory       $resultJsonFactory,
        private readonly StripTags            $tagFilter,
        private readonly PaymentApiFactory    $paymentApiFactory,
        private readonly Config               $config,
        private readonly Url                  $url,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Check for connection to server
     *
     * @return Json
     * @throws \JsonException
     */
    public function execute()
    {
        $result  = [
            'success'      => true,
            'errorMessage' => '',
        ];
        $options = $this->getRequest()->getParams();
        $isLiveMode = $options['environment'] == Environment::LIVE;

        $apiKey = $isLiveMode ? $options['secret_key'] : $options['test_secret_key'];

        if ($apiKey == '******') {
            $apiKey = $isLiveMode ? $this->config->getApiKey() : $this->config->getTestApiKey();
        }

        try {
            $api        = $this->paymentApiFactory->create(
                secretKey : $apiKey,
                isLiveMode: $isLiveMode
            );
            $currency   = $this->scopeConfig->getValue(
                'currency/options/default',
                ScopeInterface::SCOPE_STORE
            );

            $payment = $api->createEmbeddedPayment(
                new Payment(
                    new Payment\Order(
                        [
                            new Item('test', 1, 'pcs', 1, 1, 1, 'test')
                        ],
                        $currency,
                        1
                    ),
                    new Payment\EmbeddedCheckout(
                        $this->url->getUrl('checkout/onepage/success'),
                        'terms_url'
                    )
                )
            );

            if ($payment->getPaymentId()) {
                $api->terminate($payment->getPaymentId());
            }

        } catch (PaymentApiException $e) {
            $message                = $e->getMessage();
            $result['success']      = false;
            $result['errorMessage'] = $this->tagFilter->filter($message) . ' '
                . __('Please check your API key and environment.');
        }

        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($result);
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\StripTags;
use Nexi\Checkout\Model\Config\Source\Environment;
use NexiCheckout\Factory\PaymentApiFactory;

class TestConnection extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Catalog::config_catalog';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param StripTags $tagFilter
     * @param PaymentApiFactory $paymentApiFactory
     */
    public function __construct(
        Context                                          $context,
        private readonly JsonFactory                     $resultJsonFactory,
        private readonly StripTags                       $tagFilter,
        private readonly PaymentApiFactory               $paymentApiFactory,
    ) {
        parent::__construct($context);
    }

    /**
     * Check for connection to server
     *
     * @return Json
     */
    public function execute()
    {
        $result  = [
            'success'      => false,
            'errorMessage' => '',
        ];
        $options = $this->getRequest()->getParams();

        try {
            $api = $this->paymentApiFactory->create(
                secretKey : $options['secret_key'] ?? "test",
                isLiveMode: $options['environment'] == Environment::LIVE
            );

            $result = $api->retrievePayment(
                'test'
            );


            if ($result) {
                $result['success'] = true;
            }
        } catch (LocalizedException $e) {
            $result['errorMessage'] = $e->getMessage();
        } catch (\Exception $e) {
            $message                = __($e->getMessage());
            $result['errorMessage'] = $this->tagFilter->filter($message);
        }

        /** @var Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }
}

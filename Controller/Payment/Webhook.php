<?php

namespace Nexi\Checkout\Controller\Payment;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\Encryptor;
use Nexi\Checkout\Gateway\Config\Config;
use Psr\Log\LoggerInterface;

class Webhook extends Action implements CsrfAwareActionInterface, HttpPostActionInterface
{

    public function __construct(
        Context                          $context,
        private readonly LoggerInterface $logger,
        private readonly Encryptor       $encryptor,
        private readonly Config          $config,
        private readonly RequestInterface $request,
    ) {
        parent::__construct($context);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        if (!$this->isAuthorized()) {
            return $this->_response
                ->setHttpResponseCode(401)
                ->setBody('Unauthorized');
        }

        $this->webhookHandler->handle($this->getRequest()->getContent()->getParam('event'));
        // TODO: Implement webhook logic here
        $this->logger->info('Webhook called: ' . json_encode($this->getRequest()->getContent()));

        $this->_response->setHttpResponseCode(200);
    }


    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * No form key validation needed
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     *
     * @return void
     * @throws Exception
     */
    public function isAuthorized(): bool
    {
        $authString = $this->getRequest()->getHeader('Authorization');

        $hash = $this->encryptor->hash(
            $this->config->getWebhookSecret(),
        );

        return hash_equals($hash, $authString);
    }
}

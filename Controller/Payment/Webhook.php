<?php

declare(strict_types=1);

namespace Nexi\Checkout\Controller\Payment;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Serialize\SerializerInterface;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\WebhookHandler;
use Psr\Log\LoggerInterface;

class Webhook extends Action implements CsrfAwareActionInterface, HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Encryptor $encryptor
     * @param Config $config
     * @param WebhookHandler $webhookHandler
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        private readonly LoggerInterface $logger,
        private readonly Encryptor $encryptor,
        private readonly Config $config,
        private readonly WebhookHandler $webhookHandler,
        private readonly SerializerInterface $serializer
    ) {
        parent::__construct($context);
    }

    /**
     * Execute the webhook action
     *
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

        try {
            $content = $this->serializer->unserialize($this->getRequest()->getContent());
            $this->logger->info('Webhook called:', ['webhook_data' => $content]);

            if (!isset($content['event'])) {
                return $this->_response
                    ->setHttpResponseCode(400)
                    ->setBody('Missing event name');
            }

            $this->webhookHandler->handle($content);
            $this->_response->setHttpResponseCode(200);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['stacktrace' => $e->getTrace()]);
            $this->_response->setHttpResponseCode(500);
        }
    }

    /**
     * Allow all requests to this action
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
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
     * Check the authorisation header
     *
     * @return bool
     */
    public function isAuthorized(): bool
    {
        $authString = $this->getRequest()->getHeader('Authorization');

        if (empty($authString)) {
            return false;
        }

        $hash = $this->encryptor->hash(
            $this->config->getWebhookSecret(),
        );

        return hash_equals($hash, $authString);
    }
}

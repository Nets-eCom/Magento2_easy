<?php
declare(strict_types=1);

namespace Nexi\Checkout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Nexi\Checkout\Gateway\Config\Config;
use Psr\Log\LoggerInterface;

class UpdateOrderReference implements ObserverInterface
{
    /**
     * @param CommandManagerPoolInterface $commandManagerPool
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CommandManagerPoolInterface $commandManagerPool,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Add Reference Number - order increment id - to the Nexi payment after order is placed - Embedded only
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (!$this->config->isEmbedded()) {
            return;
        }
        $order = $observer->getEvent()->getOrder();
        try {
            $commandPool = $this->commandManagerPool->get(Config::CODE);
            $commandPool->executeByCode(
                commandCode: 'update_reference',
                arguments: ['order' => $order]
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['stacktrace' => $e->getTrace()]);
        }
    }
}

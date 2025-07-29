<?php
declare(strict_types=1);

namespace Nexi\Checkout\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Nexi\Checkout\Model\Subscription\Notify as SubscriptionNotify;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Notify extends Command
{
    /**
     * Constructor
     *
     * @param SubscriptionNotify $notify
     * @param State $state
     */
    public function __construct(
        private SubscriptionNotify $notify,
        private State           $state
    ) {
        parent::__construct();
    }

    /**
     * Configure
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('nexi:subscription:notify');
        $this->setDescription('Send subscription notification emails.');
    }

    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);
        $this->notify->process();

        return Cli::RETURN_SUCCESS;
    }
}

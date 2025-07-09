<?php

namespace Nexi\Checkout\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Nexi\Checkout\Model\Subscription\Bill as SubscriptionBill;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Bill extends Command
{
    /**
     * Constructor
     *
     * @param SubscriptionBill $bill
     * @param State $state
     */
    public function __construct(
        private SubscriptionBill $bill,
        private State         $state
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
        $this->setName('nexi:subscription:bill');
        $this->setDescription('Invoice customers of subscription orders');
    }

    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws LocalizedException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_CRONTAB);
        $this->bill->process();

        return Cli::RETURN_SUCCESS;
    }
}

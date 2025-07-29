<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Config\Source\Order;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\Order\Config;

class NewStatus implements OptionSourceInterface
{
    /**
     * @var string
     */
    protected string $stateStatuses = \Magento\Sales\Model\Order::STATE_NEW;

    /**
     * @param Config $orderConfig
     */
    public function __construct(
        private readonly Config $orderConfig
    ) {
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $statuses = $this->stateStatuses
            ? $this->orderConfig->getStateStatuses($this->stateStatuses)
            : $this->orderConfig->getStatuses();

        $options = [];
        foreach ($statuses as $code => $label) {
            $options[] = ['value' => $code, 'label' => $label];
        }
        return $options;
    }
}

<?php

namespace Nexi\Checkout\Model\Subscription;

use Nexi\Checkout\Model\Subscription\ActiveOrderProvider;
use Nexi\Checkout\Model\Subscription\OrderBiller;
use Nexi\Checkout\Model\ResourceModel\Subscription;

class Bill
{
    /**
     * @var OrderBiller
     */
    private $orderBiller;

    /**
     * @var ActiveOrderProvider
     */
    private $activeOrders;

    /**
     * @param OrderBiller $orderBiller
     * @param ActiveOrderProvider $activeOrderProvider
     */
    public function __construct(
        OrderBiller $orderBiller,
        ActiveOrderProvider $activeOrderProvider
    ) {
        $this->orderBiller = $orderBiller;
        $this->activeOrders = $activeOrderProvider;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process()
    {
        $validOrders = $this->getValidOrderIds();

        if (empty($validOrders)) {
            return;
        }
        $this->orderBiller->billOrdersById($validOrders);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getValidOrderIds()
    {
        return $this->activeOrders->getPayableOrderIds();
    }
}

<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderStatusHistoryInterfaceFactory;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;

class Comment
{
    /**
     * @param OrderStatusHistoryRepositoryInterface $historyRepository
     * @param OrderStatusHistoryInterfaceFactory $historyFactory
     */
    public function __construct(
        private readonly OrderStatusHistoryRepositoryInterface $historyRepository,
        private readonly OrderStatusHistoryInterfaceFactory $historyFactory
    ) {
    }

    /**
     * Save a comment to the order's status history.
     *
     * @param string $comment
     * @param Order $order
     * @throws CouldNotSaveException
     */
    public function saveComment(string|Phrase $comment, Order $order): void
    {
        $history = $this->historyFactory->create();
        $history->setComment($comment)
            ->setIsCustomerNotified(false)
            ->setStatus($order->getStatus()) // Default status, can be changed as needed
            ->setParentId($order->getId());

        $this->historyRepository->save($history);
    }
}

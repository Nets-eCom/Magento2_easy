<?php

namespace Nexi\Checkout\Model\Subscription;

use Exception;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order\Reorder\UnavailableProductsProvider;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class OrderCloner
{
    /**
     * @var CollectionFactory
     */
    private $orderCollection;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var UnavailableProductsProvider
     */
    private $unavailableProducts;
    /**
     * @var Quote
     */
    private $quoteSession;
    /**
     * @var QuoteManagement
     */
    private $quoteManagement;
    /**
     * @var JoinProcessorInterface
     */
    private $joinProcessor;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepositoryInterface;

    /**
     * @param CollectionFactory $orderCollection
     * @param UnavailableProductsProvider $unavailableProducts
     * @param Quote $quoteSession
     * @param JoinProcessorInterface $joinProcessor
     * @param QuoteManagement $quoteManagement
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $cartRepositoryInterface
     */
    public function __construct(
        CollectionFactory           $orderCollection,
        UnavailableProductsProvider $unavailableProducts,
        Quote                       $quoteSession,
        JoinProcessorInterface      $joinProcessor,
        QuoteManagement             $quoteManagement,
        LoggerInterface             $logger,
        CartRepositoryInterface     $cartRepositoryInterface
    ) {
        $this->orderCollection = $orderCollection;
        $this->unavailableProducts = $unavailableProducts;
        $this->quoteSession = $quoteSession;
        $this->joinProcessor = $joinProcessor;
        $this->quoteManagement = $quoteManagement;
        $this->logger = $logger;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
    }

    /**
     * Clones orders by existing order ids, if performance becomes an issue. Consider limiting results from
     * @param int[] $orderIds
     * @return \Magento\Sales\Model\Order[]
     * @see \Nexi\Checkout\Model\ResourceModel\Subscription::getClonableOrderIds
     *
     */
    public function cloneOrders($orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $orderCollection = $this->orderCollection->create();
        $orderCollection->addFieldToFilter('entity_id', $orderIds);
        $this->joinProcessor->process($orderCollection);
        $newOrders = [];

        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orderCollection as $order) {
            try {
                $clonedOrder = $this->clone($order);
                $newOrders[$clonedOrder->getId()] = $clonedOrder;
            } catch (Exception $exception) {
                $this->logger->error(__(
                    'Recurring payment order cloning error: %error',
                    ['error' => $exception->getMessage()]
                ));
                continue;
            }
        }

        return $newOrders;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order $oldOrder
     * @throws LocalizedException
     */
    private function clone(
        \Magento\Sales\Api\Data\OrderInterface $oldOrder
    ) {
        $this->validateOrder($oldOrder);

        $this->quoteSession->clearStorage();
        $this->quoteSession->setData('use_old_shipping_method', true);
        $oldOrder->setData('reordered', true);

        $quote = $this->getQuote($oldOrder);

        $this->removeNonScheduledProducts($quote);

        return $this->quoteManagement->submit($quote);
    }

    /**
     * @param $quote
     * @return void
     */
    private function removeNonScheduledProducts($quote): void
    {
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if (!$quoteItem->getProduct()->getSubscriptionSchedule()) {
                $quote->deleteItem($quoteItem);
                $quote->setTotalsCollectedFlag(false);
            }
        }

        $quote->save();
        $quote->collectTotals();
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    private function validateOrder($order)
    {
        if ($order->canReorder()
            && count($this->unavailableProducts->getForOrder($order)) == 0
        ) {
            return true;
        }

        throw new LocalizedException(__(
            'Order id: %id cannot be reordered',
            ['id' => $order->getId()]
        ));
    }

    /**
     * @param \Magento\Sales\Model\Order $oldOrder
     * @return \Magento\Quote\Model\Quote
     * @throws LocalizedException
     */
    private function getQuote(\Magento\Sales\Api\Data\OrderInterface $oldOrder): \Magento\Quote\Model\Quote
    {
        $quote = $this->cartRepositoryInterface->get($oldOrder->getQuoteId());
        $quote->setData('recurring_payment_flag', true);

        return $quote;
    }
}

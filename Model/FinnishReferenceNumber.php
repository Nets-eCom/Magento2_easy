<?php

namespace Nexi\Checkout\Model;

use Magento\Sales\Model\Order;
use Nexi\Checkout\Exceptions\CheckoutException;
use Nexi\Checkout\Gateway\Config\Config;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

class FinnishReferenceNumber
{
    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $criteriaBuilderFactory;

    /**
     * FinnishReferenceNumber constructor.
     *
     * @param Config $gatewayConfig
     * @param OrderFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilderFactory $criteriaBuilderFactory
     */
    public function __construct(
        Config $gatewayConfig,
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilderFactory $criteriaBuilderFactory
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->criteriaBuilderFactory = $criteriaBuilderFactory;
    }

    /**
     * @param mixed $reference
     *
     * @return Order
     * @throws \Exception
     */
    public function getOrderByReference(mixed $reference): Order
    {
        if (!$this->gatewayConfig->getGenerateReferenceForOrder()) {
            return $this->orderFactory->create()->loadByIncrementId($reference);
        }

        $criteriaBuilder = $this->criteriaBuilderFactory->create();
        $searchCriteria        = $criteriaBuilder->addFilter('finnish_reference_number', $reference)
                                                 ->create();

        /** @var Order[] $orders */
        $orders = $this->orderRepository->getList($searchCriteria)
                                        ->getItems();

        if (count($orders) > 1) {
            throw new CheckoutException(__('Multiple orders found with same reference number'));
        }

        return reset($orders);
    }

    /**
     * Get order increment id from checkout reference number
     *
     * @param string $reference
     *
     * @return string|null
     * @throws \Exception
     */
    public function getIdFromOrderReferenceNumber(string $reference): ?string
    {
        return $this->getOrderByReference($reference)->getIncrementId();
    }

    /**
     * Calculate Finnish reference number from order increment id
     * according to Finnish reference number algorithm
     * if increment id is not numeric - letters will be converted to numbers -> (ord($letter) % 10)
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return string
     * @throws \Nexi\Checkout\Exceptions\CheckoutException
     */
    public function calculateOrderReferenceNumber(Order $order): string
    {
        $numericIncrementId = preg_replace('/\D/', '', $order->getIncrementId());

        $prefixedId = $order->getStoreId() . $numericIncrementId;
        if ($prefixedId[0] === '0') {
            $prefixedId = '1' . $prefixedId;
        }

        $sum    = 0;
        $length = strlen($prefixedId);

        for ($i = 0; $i < $length; ++$i) {
            $substr       = substr($prefixedId, -1 - $i, 1);
            $numSubstring = is_numeric($substr) ? (int)$substr : (ord($substr) % 10);

            $sum += $numSubstring * [7, 3, 1][$i % 3];
        }
        $num          = (10 - $sum % 10) % 10;
        $referenceNum = $prefixedId . $num;

        if ($referenceNum > 9999999999999999999) {
            throw new CheckoutException('Order reference number is too long');
        }

        $asString = trim(chunk_split($referenceNum, 5, ' '));

        $order->setFinnishReferenceNumber($asString);
        $this->orderRepository->save($order);

        return $asString;
    }

    /**
     * @param Order $order
     *
     * @return string reference number
     * @throws \Nexi\Checkout\Exceptions\CheckoutException
     */
    public function getReference(Order $order): string
    {
        if ($order->getFinnishReferenceNumber()) {
            return $order->getFinnishReferenceNumber();
        }

        if (!$this->gatewayConfig->getGenerateReferenceForOrder() && $order->getIncrementId()) {
            return $order->getIncrementId();
        }

        return $order->getExtensionAttributes()->getFinnishReferenceNumber()
            ?: $this->calculateOrderReferenceNumber($order);
    }
}

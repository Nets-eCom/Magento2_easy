<?php

namespace Dibs\EasyCheckout\Model;

use Dibs\EasyCheckout\Helper\SwishResponseHandler;
use Dibs\EasyCheckout\Model\Cache\PaymentMutex;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Magento\Sales\Model\OrderFactory as OrderFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class CheckoutContext
{
    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    protected $helper;

    /**
     * @var \Dibs\EasyCheckout\Logger\Logger
     */
    protected $logger;

    /** @var \Dibs\EasyCheckout\Model\Dibs\Order $dibsOrderHandler */
    protected $dibsOrderHandler;

    /** @var \Magento\Sales\Api\OrderCustomerManagementInterface */
    protected $orderCustomerManagement;

    /** @var \Magento\Newsletter\Model\Subscriber $Subscriber */
    protected $subscriber;

    /** @var \Dibs\EasyCheckout\Model\Dibs\Locale $dibsLocale */
    protected $dibsLocale;

    /** @var OrderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var OrderResourceFactory */
    protected $orderResourceFactory;

    /** @var OrderFactory */
    protected $orderFactory;

    /** @var OrderRepositoryInterface */
    protected $orderRepo;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var OrderSender */
    protected $orderSender;

    /**
     * @var SwishResponseHandler
     */
    private $swishHandler;

    /**
     * @var PaymentMutex
     */
    private $paymentMutex;

    /**
     * Constructor
     *
     * @param \Dibs\EasyCheckout\Helper\Data $helper
     * @param \Dibs\EasyCheckout\Model\Dibs\Order $dibsOrderHandler
     * @param \Dibs\EasyCheckout\Logger\Logger $logger
     * @param \Dibs\EasyCheckout\Model\Dibs\Locale $dibsLocale ,
     * @param \Magento\Sales\Api\OrderCustomerManagementInterface $orderCustomerManagement
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param SwishResponseHandler $swishHandler
     * @param PaymentMutex $paymentMutex
     */
    public function __construct(
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Dibs\EasyCheckout\Model\Dibs\Order $dibsOrderHandler,
        \Dibs\EasyCheckout\Logger\Logger $logger,
        \Dibs\EasyCheckout\Model\Dibs\Locale $dibsLocale,
        \Magento\Sales\Api\OrderCustomerManagementInterface $orderCustomerManagement,
        OrderCollectionFactory $orderCollectionFactory,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        SwishResponseHandler $swishHandler,
        PaymentMutex $paymentMutex,
        OrderResourceFactory $orderResourceFactory,
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderSender $orderSender
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->dibsOrderHandler = $dibsOrderHandler;
        $this->dibsLocale = $dibsLocale;
        $this->orderCustomerManagement = $orderCustomerManagement;
        $this->subscriber = $subscriber;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->swishHandler = $swishHandler;
        $this->paymentMutex = $paymentMutex;
        $this->orderResourceFactory = $orderResourceFactory;
        $this->orderFactory = $orderFactory;
        $this->orderRepo = $orderRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderSender = $orderSender;
    }

    /**
     * @return \Dibs\EasyCheckout\Helper\Data
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * @return \Dibs\EasyCheckout\Logger\Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return Dibs\Order
     */
    public function getDibsOrderHandler()
    {
        return $this->dibsOrderHandler;
    }

    /**
     * @return \Magento\Sales\Api\OrderCustomerManagementInterface
     */
    public function getOrderCustomerManagement()
    {
        return $this->orderCustomerManagement;
    }

    /**
     * @return \Magento\Newsletter\Model\Subscriber
     */
    public function getSubscriber()
    {
        return $this->subscriber;
    }

    /**
     * @return Dibs\Locale
     */
    public function getDibsLocale()
    {
        return $this->dibsLocale;
    }

    /**
     * @return OrderCollectionFactory
     */
    public function getOrderCollectionFactory()
    {
        return $this->orderCollectionFactory;
    }

    /**
     * @return SwishResponseHandler
     */
    public function getSwishHandler(): SwishResponseHandler
    {
        return $this->swishHandler;
    }

    /**
     * @return PaymentMutex
     */
    public function getPaymentMutex(): PaymentMutex
    {
        return $this->paymentMutex;
    }

    /**
     * @return OrderResourceFactory
     */
    public function getOrderResourceFactory(): OrderResourceFactory
    {
        return $this->orderResourceFactory;
    }

    /**
     * @return OrderFactory
     */
    public function getOrderFactory(): OrderFactory
    {
        return $this->orderFactory;
    }

    /**
     * @return OrderRepository
     */
    public function getOrderRepository(): OrderRepository
    {
        return $this->orderRepo;
    }

    /**
     * @return OrderSender
     */
    public function getOrderSender()
    {
        return $this->orderSender;
    }

    /**
     * Load order with state pending_payment by nets payment ID
     *
     * @param string $paymentId
     * @return Order
     */
    public function loadPendingOrder($paymentId)
    {
        // For consistent return value, return empty order object on no result
        $noResult = $this->orderFactory->create();
        $search = $this->searchCriteriaBuilder
            ->addFilter('dibs_payment_id', $paymentId)
            ->addFilter(Order::STATE, Order::STATE_PENDING_PAYMENT)
            ->setPageSize(1)
            ->create()
        ;

        $result = $this->orderRepo->getList($search);
        if ($result->getTotalCount() > 0) {
            return current($result->getItems());
        }

        return $noResult;
    }

    /**
     * Load order by paymentId only
     *
     * @param string $paymentId
     * @return Order
     */
    public function loadOrder($paymentId)
    {
        // For consistent return value, return empty order object on no result
        $noResult = $this->orderFactory->create();
        $search = $this->searchCriteriaBuilder
            ->addFilter('dibs_payment_id', $paymentId)
            ->setPageSize(1)
            ->create()
        ;

        $result = $this->orderRepo->getList($search);
        if ($result->getTotalCount() > 0) {
            return current($result->getItems());
        }

        return $noResult;
    }
}

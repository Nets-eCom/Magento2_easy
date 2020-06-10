<?php
namespace Dibs\EasyCheckout\Model\Payment\Method;


/**
 * Nets EasyCheckout Abstract Payment method
 */
abstract class AbstractMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * @var \Dibs\EasyCheckout\Helper\Data
     */
    protected $_helper;

    /** @var \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi */
    protected $paymentApi;

    /** @var \Dibs\EasyCheckout\Model\Dibs\Items $itemsHandler */
    protected $itemsHandler;

    /**
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $_currencyFactory;

    /** @var \Dibs\EasyCheckout\Model\Dibs\Order $dibsHandler */
    protected $dibsHandler;



    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Dibs\EasyCheckout\Helper\Data $helper
     * @param \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi
     * @param \Dibs\EasyCheckout\Model\Dibs\Items $itemsHandler
     * @param \Dibs\EasyCheckout\Model\Dibs\Order $dibsHandler
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi,
        \Dibs\EasyCheckout\Model\Dibs\Items $itemsHandler,
        \Dibs\EasyCheckout\Model\Dibs\Order $dibsHandler,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null, //not required params need to be at the end of the list, else Cannot instantiate abstract class Magento\Framework\Model\ResourceModel\AbstractResource
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,

        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_currencyFactory = $currencyFactory;

        $this->itemsHandler = $itemsHandler;
        $this->paymentApi = $paymentApi;
        $this->dibsHandler = $dibsHandler;


        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }




}

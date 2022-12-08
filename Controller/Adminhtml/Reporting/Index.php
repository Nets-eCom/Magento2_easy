<?php
namespace Dibs\EasyCheckout\Controller\Adminhtml\Reporting;


use Dibs\EasyCheckout\Model\Client\DTO\ReportingRequest;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    
    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $moduleResource;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi,
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\ResourceInterface $moduleResource
    )
    {
        parent::__construct($context);
        $this->resultFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->paymentApi = $paymentApi;
        $this->_storeManager = $storeManager;
        $this->moduleResource = $moduleResource;
    }
    
    public function execute(){
        
        
        $version = $this->moduleResource->getDbVersion('Dibs_EasyCheckout');
        $reportingRequest = new ReportingRequest();
        $reportingRequest->setMerchantId($this->helper->getMerchantId()); 
        $reportingRequest->setPluginName("Magento2"); 
        $reportingRequest->setPluginVersion($version); 
        $reportingRequest->setShopUrl($this->_storeManager->getStore()->getBaseUrl()); 
        $reportingRequest->setIntegrationType($this->helper->getCheckoutFlow()); 
        $reportingRequest->setTimeStamp(date('Y-m-d H:i:s')); 
	
	try {
            $response = $this->paymentApi->reportingApi($reportingRequest);
            $resultJson = $this->resultFactory->create();
        
            return $resultJson->setData([
                 'message' => $response->getData(),
                 'status' => $response->getStatus()
            ]);
        } catch (ClientException $e) {
            // handle?
            throw $e;
        }
    
    }
}

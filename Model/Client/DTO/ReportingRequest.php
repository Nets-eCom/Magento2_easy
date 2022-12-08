<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;

class ReportingRequest extends AbstractRequest
{

    /**
     * Required
     * @var string $merchant_id
     */
    protected $merchant_id;

    /**
     * Required
     * @var string $plugin_name
     */
    protected $plugin_name;
    
    /**
     * Required
     * @var string $plugin_version
     */
    protected $plugin_version;
    
    /**
     * Required
     * @var string $shop_url
     */
    protected $shop_url;
    
    /**
     * Required
     * @var string $integration_type
     */
    protected $integration_type;
    
    /**
     * Required
     * @var string $timestamp
     */
    protected $timestamp;
    
    
    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * @param string $merchant_id
     * @return ReportingRequest
     */
    public function setMerchantId($merchant_id)
    {
        $this->merchant_id = $merchant_id;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getPluginName()
    {
        return $this->plugin_name;
    }

    /**
     * @param string $plugin_name
     * @return ReportingRequest
     */
    public function setPluginName($plugin_name)
    {
        $this->plugin_name = $plugin_name;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->plugin_version;
    }

    /**
     * @param string $plugin_version
     * @return ReportingRequest
     */
    public function setPluginVersion($plugin_version)
    {
        $this->plugin_version = $plugin_version;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getShopUrl()
    {
        return $this->shop_url;
    }

    /**
     * @param string $shop_url
     * @return ReportingRequest
     */
    public function setShopUrl($shop_url)
    {
        $this->shop_url = $shop_url;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getIntegrationType()
    {
        return $this->integration_type;
    }

    /**
     * @param string $integration_type
     * @return ReportingRequest
     */
    public function setIntegrationType($integration_type)
    {
        $this->integration_type = $integration_type;
        return $this;
    }

    /**
     * @return string
     */
    public function getTimeStamp()
    {
        return $this->timestamp;
    }

    /**
     * @param string $timestamp
     * @return ReportingRequest
     */
    public function setTimeStamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function toJSON()
    {
        return json_encode(
            $this->utf8ize($this->toArray())
        );
    }

    public function toArray()
    {
        return [
            'merchant_id' => $this->getMerchantId(),
            'plugin_name' => $this->getPluginName(),
            'plugin_version' => $this->getPluginVersion(),
            'shop_url' => $this->getShopUrl(),
            'integration_type' => $this->getIntegrationType(),
            'timestamp' => $this->getTimeStamp()
        ];
    }


}
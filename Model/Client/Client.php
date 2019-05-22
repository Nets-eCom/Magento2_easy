<?php
namespace Dibs\EasyCheckout\Model\Client;


use GuzzleHttp\RequestOptions;
use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

/**
 * Class Client
 * @package Dibs\EasyCheckout\Model\Api
 */
abstract class Client
{

    /**
     * @var int
     */
    protected $timeout = 30;

    /** @var Context $apiContext */
    protected $apiContext;

    /** @var string $apiSecretKey */
    private $apiSecretKey;

    /** @var \GuzzleHttp\Client $httpClient */
    private $httpClient;

    /**
     * Constructor
     *
     * @param Context $apiContext
     *
     */
    public function __construct(
        Context $apiContext
    ) {
        $this->apiContext = $apiContext;

        $this->apiSecretKey = $apiContext->getHelper()->getApiSecretKey();
        // init curl!
        $this->setGuzzleHttpClient($this->getHelper()->getApiUrl());
    }

    /**
     * @param $baseUrl
     */
    private function setGuzzleHttpClient($baseUrl)
    {
        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => $baseUrl,
        //    'verify' => false,
        ]);
    }


    /**
     * @param $endpoint
     * @param array $params
     * @param bool $useTenant
     * @return string
     */
    protected function buildEndpoint($endpoint, $params = []) {
        $buildEndpoint = ltrim($endpoint, "/");
        if (!empty($params)) {
            $query =  http_build_query($params);
            $buildEndpoint .= "?" . $query;
        }

        return $buildEndpoint;
    }

    /**
     * @param $endpoint
     * @param array $options
     * @return string
     * @throws \Exception
     */
    protected function get($endpoint, $options = []){
        if (!is_array($options)) {
            $options = [];
        }

        $options = array_merge($options, $this->getDefaultOptions());
        $result = $this->httpClient->get($endpoint, $options);

        return $result->getBody()->getContents();
    }

    /**
     * @param $endpoint
     * @param AbstractRequest $request
     * @param array $options
     * @return string
     * @throws \Exception
     */
    protected function post($endpoint, AbstractRequest $request, $options = []){
        if (!is_array($options)) {
            $options = [];
        }

        $options = array_merge($options, $this->getDefaultOptions());
        $options[RequestOptions::JSON] = $request->toArray();

        // todo catch exceptions or let them be catched by magento?
        try {
            $result = $this->httpClient->post($endpoint, $options);
        } catch (\Exception $e) {
            $this->getLogger()->error("Failed sending request to dibs integration: POST $endpoint");
            $this->apiContext->getLogger()->error(json_encode($this->removeAuthForLogging($options)));
            $this->getLogger()->error($request->toJSON());
            $this->apiContext->getLogger()->error("Message: " . $e->getMessage());
            throw $e;
        }

        return $result->getBody()->getContents();
    }

    /**
     * @param $endpoint
     * @param AbstractRequest $request
     * @param array $options
     * @return string
     * @throws \Exception
     */
    protected function patch($endpoint, AbstractRequest $request, $options = []){
        if (!is_array($options)) {
            $options = [];
        }

        $options = array_merge($options, $this->getDefaultOptions());
        $options['json'] = $request;

        try {
            $result = $this->httpClient->patch($endpoint, $options);
        } catch (\Exception $e) {
            $this->getLogger()->error("Failed sending request to dibs integration: PATCH $endpoint");
            $this->getLogger()->error(json_encode($this->removeAuthForLogging($options)));
            $this->getLogger()->error($request->toJSON());
            $this->getLogger()->error("Message: " . $e->getMessage());
            throw $e;
        }

        return $result->getBody()->getContents();
    }

    /**
     * @return mixed
     */
    protected function getDefaultOptions()
    {

        $options['headers'] = [
            'Authorization' => $this->apiSecretKey,
        ];

        return $options;
    }

    private function removeAuthForLogging($options) {

        // we dont want to expose these values!
        if (isset($options['headers']['Authorization'])) {
            unset($options['headers']['Authorization']);
        }

        return $options;
    }

    /**
     * @return \Dibs\EasyCheckout\Logger\Logger
     */
    public function getLogger()
    {
        return $this->apiContext->getLogger();
    }

    /**
     * @return \Dibs\EasyCheckout\Helper\Data
     */
    public function getHelper()
    {
        return $this->apiContext->getHelper();
    }

}










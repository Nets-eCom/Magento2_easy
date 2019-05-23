<?php
namespace Dibs\EasyCheckout\Model\Client;


use GuzzleHttp\Exception\BadResponseException;
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

        try {
            $result = $this->httpClient->get($endpoint, $options);
            return $result->getBody()->getContents();
        } catch (BadResponseException $e) {
            $exception = $this->handleException($e);
        } catch (\Exception $e) {
            $exception = $this->handleException($e);
        }

        if ($exception) {
            $this->getLogger()->error("Failed sending request to dibs integration: GET $endpoint");
            $this->getLogger()->error(json_encode($this->removeAuthForLogging($options)));
            $this->getLogger()->error($exception->getMessage());
            $this->getLogger()->error($exception->getHttpStatusCode());
            $this->getLogger()->error($exception->getResponseBody());
            throw $exception;
        }
    }

    /**
     * @param $endpoint
     * @param AbstractRequest $request
     * @param array $options
     * @return string
     * @throws ClientException
     */
    protected function post($endpoint, AbstractRequest $request, $options = []){
        if (!is_array($options)) {
            $options = [];
        }

        $options = array_merge($options, $this->getDefaultOptions());
        $options[RequestOptions::JSON] = $request->toArray();
        $exception = null;

        // todo catch exceptions or let them be catched by magento?
        try {
            $result = $this->httpClient->post($endpoint, $options);
            return $result->getBody()->getContents();
        } catch (BadResponseException $e) {
            $exception = $this->handleException($e);
        } catch (\Exception $e) {
            $exception = $this->handleException($e);
        }

        if ($exception) {
            $this->getLogger()->error("Failed sending request to dibs integration: POST $endpoint");
            $this->getLogger()->error(json_encode($this->removeAuthForLogging($options)));
            $this->getLogger()->error($request->toJSON());
            $this->getLogger()->error($exception->getMessage());
            $this->getLogger()->error($exception->getHttpStatusCode());
            $this->getLogger()->error($exception->getResponseBody());
            throw $exception;
        }

    }

    /**
     * @param $endpoint
     * @param AbstractRequest $request
     * @param array $options
     * @return string
     * @throws \Exception
     */
    protected function put($endpoint, AbstractRequest $request, $options = []){
        if (!is_array($options)) {
            $options = [];
        }

        $options = array_merge($options, $this->getDefaultOptions());
        $options['json'] = $request;
        $exception = null;

        try {
            $result = $this->httpClient->put($endpoint, $options);
            return $result->getBody()->getContents();
        }  catch (BadResponseException $e) {
            $exception = $this->handleException($e);
        } catch (\Exception $e) {
            $exception = $this->handleException($e);
        }

        if ($exception) {
            $this->getLogger()->error("Failed sending request to dibs integration: PUT $endpoint");
            $this->getLogger()->error(json_encode($this->removeAuthForLogging($options)));
            $this->getLogger()->error($request->toJSON());
            $this->getLogger()->error($exception->getMessage());
            $this->getLogger()->error($exception->getHttpStatusCode());
            $this->getLogger()->error($exception->getResponseBody());
            throw $exception;
        }
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
     *
     * @throws ClientException
     */
    private function handleException(\Exception $e)
    {
        if ($e instanceof BadResponseException) {
            if ($e->hasResponse()) {
                return new ClientException($e->getRequest(),$e->getResponse(), $e->getMessage(),$e->getCode(), $e);
            } else {
                return new ClientException($e->getRequest(), null, $e->getMessage(), $e->getCode());
            }
        } else if($e instanceof \Exception) {
            return new ClientException(null, null, $e->getMessage(), $e->getCode());
        }
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










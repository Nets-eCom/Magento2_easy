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
    const COMMERCE_PLATFORM_TAG = 'Magento2';

    /**
     * @var int
     */
    protected $timeout = 30;

    /** @var Context $apiContext */
    protected $apiContext;

    /** @var string $apiSecretKey */
    private $apiSecretKey;


    /** @var bool $testMode */
    protected $testMode;


    private \GuzzleHttp\Client $httpClient;

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
        $this->testMode = $apiContext->getHelper()->isTestMode();

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
     * @throws ClientException
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
     * @param $mixed
     *
     * @return array|false|mixed|string|string[]|null
     */
    public function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
        }

        return $mixed;
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
        $options[RequestOptions::JSON] = $this->utf8ize($request->toArray());

        $exception = null;

        try {
            $result = $this->httpClient->post($endpoint, $options);
            $content =  $result->getBody()->getContents();


            if ($this->testMode) {
                $this->getLogger()->info("Sending request to dibs integration: POST $endpoint");
                $this->getLogger()->info($request->toJSON());

                $this->getLogger()->info("Response Headers from dibs:");
                $this->getLogger()->info(json_encode($result->getHeaders()));
                $this->getLogger()->info("Response Body from dibbs:");
                $this->getLogger()->info($content);
            }

            return $content;
        } catch (BadResponseException $e) {
            $exception = $this->handleException($e);
        } catch (\Exception $e) {
            $exception = $this->handleException($e);
        }

        if ($exception) {
            $this->getLogger()->error("Failed sending request to dibs integration: POST $endpoint");
            $this->getLogger()->error(json_encode($this->removeAuthForLogging($options)));
            $this->getLogger()->error($request->toJSON());
            $this->getLogger()->error(mb_convert_encoding($exception->getMessage(), "UTF-8", "UTF-8"));
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
    protected function put($endpoint, AbstractRequest $request, $options = []){
        if (!is_array($options)) {
            $options = [];
        }

        $options = array_merge($options, $this->getDefaultOptions());
        $options[RequestOptions::JSON] = $request->toArray();
        $exception = null;

        try {
            $this->getLogger()->info("put request initiated");
            $result = $this->httpClient->put($endpoint, $options);
            $content =  $result->getBody()->getContents();
            $this->getLogger()->info("put request called");
           // if ($this->testMode) {
                $this->getLogger()->info("Sending request to dibs integration: PUT $endpoint");
                $this->getLogger()->info($request->toJSON());

                $this->getLogger()->info("Response Headers from dibs:");
                $this->getLogger()->info(json_encode($result->getHeaders()));
                $this->getLogger()->info("Response Body from dibbs:");
                $this->getLogger()->info($content);
           // }

            return $content;
        }  catch (BadResponseException $e) {
            $exception = $this->handleException($e);
            $this->getLogger()->info("api exception 1 :" . $exception->getMessage());
        } catch (\Exception $e) {
            $exception = $this->handleException($e);
            $this->getLogger()->info("api exception 2 :" . $exception->getMessage());
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
            'Content-Type' => 'application/json',
            'commercePlatformTag' => self::COMMERCE_PLATFORM_TAG,
            'Authorization' => $this->apiContext->getHelper()->getApiSecretKey(),
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
     * @param \Exception $e
     * @return ClientException
     */
    private function handleException(\Exception $e)
    {
        if ($e instanceof BadResponseException) {
            if ($e->hasResponse()) {
                return new ClientException($e->getRequest(),$e->getResponse(), $e->getMessage(),$e->getCode(), $e);
            } else {
                return new ClientException($e->getRequest(), null, $e->getMessage(), $e->getCode());
            }
        }

        return new ClientException(null, null, $e->getMessage(), $e->getCode());
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










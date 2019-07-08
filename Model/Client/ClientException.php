<?php


namespace Dibs\EasyCheckout\Model\Client;

use \Exception;
use \Throwable;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


class ClientException extends Exception
{

    /** @var RequestInterface $request */
    protected $request;

    /** @var ResponseInterface $response */
    protected $response;


    /** @var string $responseBody */
    protected $responseBody = "";

    protected $method;

    protected $url;


    public function __construct($request = null, $response = null, $message = "", $code = 0, Throwable $previous = null) {
        $this->request = $request;
        $this->response = $response;

        $message = $this->buildMessage($response, $message);

        parent::__construct($message, $code, $previous);
    }


    public function getHttpStatusCode()
    {
        if ($this->response) {
            return $this->response->getStatusCode();
        }

        return null;
    }

    public function getResponseBody()
    {
        return $this->responseBody;
    }

    protected function buildMessage(ResponseInterface $response, $fallbackMessage) {
        if (!$response) {
            return $fallbackMessage;
        }

        $this->responseBody = $response->getBody()->getContents();

        // try to parse the response body with messages
        try {
            $content = json_decode($this->responseBody, true);
            if (isset($content['errors'])) {
                $errors = [];
                foreach ($content['errors'] as $errArray) {
                    foreach ($errArray as $error) {
                        $errors[] = $error;
                    }
                }

                if ($response->getStatusCode() >= 500) {
                    return "Dibs are experiencing technical issues. Try again, or contact the site admin! " . "Dibs Error: " . implode(". ", $errors);
                }

                return "Dibs Error: " . implode(". ", $errors);
            }

            if (isset($content['message'])) {
                $errMsg = $content['message'];
                if ($response->getStatusCode() >= 500) {
                    return "Dibs are experiencing technical issues. Try again, or contact the site admin! " . "Dibs Error: " . $errMsg;
                }

                return "Dibs Error: " . $errMsg;
            }

            return $fallbackMessage;
        } catch (\Exception $e) {
            return $fallbackMessage;
        }
    }




}
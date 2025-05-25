<?php

namespace Nexi\Checkout\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Gateway\Validator\HmacValidator;

class ResponseValidator extends AbstractValidator
{
    /**
     * ResponseValidator constructor.
     *
     * @param Config $gatewayConfig
     * @param ResultInterfaceFactory $resultFactory
     * @param HmacValidator $hmacValidator
     */
    public function __construct(
        private Config $gatewayConfig,
        ResultInterfaceFactory $resultFactory,
        private HmacValidator $hmacValidator
    ) {
        parent::__construct($resultFactory);
    }

    /**
     * Validate.
     *
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $isValid = true;
        $fails = [];

        if (isset($validationSubject["skip_validation"]) && $validationSubject["skip_validation"] == 1) {
            return $this->createResult($isValid, $fails);
        }

        if ($this->isRequestMerchantIdEmpty($this->gatewayConfig->getMerchantId())) {
            $fails[] = "Request MerchantId is empty";
        }

        if ($this->isResponseMerchantIdEmpty($validationSubject["checkout-account"])) {
            $fails[] = "Response MerchantId is empty";
        }

        if ($this->isMerchantIdValid($validationSubject["checkout-account"]) == false) {
            $fails[] = "Response and Request merchant ids does not match";
        }

        if ($this->validateResponse($validationSubject) == false) {
            $fails[] = "Invalid response data from Nexi";
        }

        if ($this->validateAlgorithm($validationSubject["checkout-algorithm"]) == false) {
            $fails[] = "Invalid response data from Nexi";
        }

        if (count($fails) > 0) {
            $isValid = false;
        }
        return $this->createResult($isValid, $fails);
    }

    /**
     * Is merchant ID is valid.
     *
     * @param string $responseMerchantId
     * @return bool
     */
    public function isMerchantIdValid($responseMerchantId)
    {
        $requestMerchantId = $this->gatewayConfig->getMerchantId();
        if ($requestMerchantId == $responseMerchantId) {
            return true;
        }

        return false;
    }

    /**
     * Is request Merchant ID empty.
     *
     * @param string $requestMerchantId
     * @return bool
     */
    public function isRequestMerchantIdEmpty($requestMerchantId)
    {
        return empty($requestMerchantId);
    }

    /**
     * Is sponse merchant ID empty.
     *
     * @param string $responseMerchantId
     * @return bool
     */
    public function isResponseMerchantIdEmpty($responseMerchantId)
    {
        return empty($responseMerchantId);
    }

    /**
     * Validate algorithm.
     *
     * @param string $algorithm
     * @return bool
     */
    public function validateAlgorithm($algorithm)
    {
        return in_array($algorithm, $this->gatewayConfig->getValidAlgorithms(), true);
    }

    /**
     * Validate response.
     *
     * @param array $params
     * @return bool
     */
    public function validateResponse($params)
    {
        return $this->hmacValidator->validateHmac($params, $params["signature"]);
    }
}

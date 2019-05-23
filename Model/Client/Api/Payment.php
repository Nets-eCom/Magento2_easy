<?php


namespace Dibs\EasyCheckout\Model\Client\Api;

use Dibs\EasyCheckout\Model\Client\Client;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePayment;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePaymentResponse;

class Payment extends Client
{

    /**
     * @param CreatePayment $createPayment
     * @return CreatePaymentResponse
     * @throws \Exception
     */
    public function createNewPayment(CreatePayment $createPayment)
    {
        try {
            $response = $this->post("/v1/payments", $createPayment);
        } catch (\Exception $e) {
            // handle?
            throw $e;
        }

        return new CreatePaymentResponse($response);
    }

}
<?php


namespace Dibs\EasyCheckout\Model\Client\Api;

use Dibs\EasyCheckout\Model\Client\Client;
use Dibs\EasyCheckout\Model\Client\ClientException;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePayment;
use Dibs\EasyCheckout\Model\Client\DTO\GetPaymentResponse;
use Dibs\EasyCheckout\Model\Client\DTO\UpdatePaymentCart;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePaymentResponse;
use Dibs\EasyCheckout\Model\Client\DTO\UpdatePaymentReference;

class Payment extends Client
{

    /**
     * @param CreatePayment $createPayment
     * @return CreatePaymentResponse
     * @throws ClientException
     */
    public function createNewPayment(CreatePayment $createPayment)
    {
        try {
            $response = $this->post("/v1/payments", $createPayment);
        } catch (ClientException $e) {
            // handle?
            throw $e;
        }

        return new CreatePaymentResponse($response);
    }


    /**
     * @param UpdatePaymentCart $cart
     * @param $paymentId
     * @return bool
     * @throws \Exception
     */
    public function UpdatePaymentCart(UpdatePaymentCart $cart, $paymentId)
    {
        try {
            $this->put("/v1/payments/".$paymentId."/orderitems", $cart);
        } catch (ClientException $e) {
            // handle?
            throw $e;
        }

        return true;
    }

    /**
     * @param UpdatePaymentReference $reference
     * @param $paymentId
     * @return bool
     * @throws ClientException
     */
    public function UpdatePaymentReference(UpdatePaymentReference $reference, $paymentId)
    {
        try {
            $this->put("/v1/payments/".$paymentId."/referenceinformation", $reference);
        } catch (ClientException $e) {
            // handle?
            throw $e;
        }

        return true;
    }

    /**
     * @param string $paymentId
     * @return GetPaymentResponse
     * @throws ClientException
     */
    public function getPayment($paymentId)
    {
        try {
            $response = $this->get("/v1/payments/" . $paymentId);
        } catch (ClientException $e) {
            // handle?
            throw $e;
        }

        return new GetPaymentResponse($response);
    }

}
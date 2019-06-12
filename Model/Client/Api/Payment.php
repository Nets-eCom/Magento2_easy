<?php


namespace Dibs\EasyCheckout\Model\Client\Api;

use Dibs\EasyCheckout\Model\Client\Client;
use Dibs\EasyCheckout\Model\Client\ClientException;
use Dibs\EasyCheckout\Model\Client\DTO\CancelPayment;
use Dibs\EasyCheckout\Model\Client\DTO\ChargePayment;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePayment;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePaymentChargeResponse;
use Dibs\EasyCheckout\Model\Client\DTO\CreateRefundResponse;
use Dibs\EasyCheckout\Model\Client\DTO\GetPaymentResponse;
use Dibs\EasyCheckout\Model\Client\DTO\RefundPayment;
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
     * @return void
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

    }

    /**
     * @param UpdatePaymentReference $reference
     * @param $paymentId
     * @return void
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


    /**
     * @param CancelPayment $payment
     * @param string $paymentId
     * @throws ClientException
     * @return void
     */
    public function cancelPayment(CancelPayment $payment, $paymentId)
    {
        try {
            $this->post("/v1/payments/" . $paymentId . "/cancels", $payment);
        } catch (ClientException $e) {
            // handle?
            throw $e;
        }
    }

    /**
     * @param ChargePayment $payment
     * @param string $paymentId
     * @throws ClientException
     * @return CreatePaymentChargeResponse
     */
    public function chargePayment(ChargePayment $payment, $paymentId)
    {
        try {
            $response = $this->post("/v1/payments/" . $paymentId . "/charges", $payment);
        } catch (ClientException $e) {
            // handle?
            throw $e;
        }

        return new CreatePaymentChargeResponse($response);
    }


    /**
     * @param RefundPayment $paymentCharge
     * @param string $chargeId
     * @throws ClientException
     * @return CreateRefundResponse
     */
    public function refundPayment(RefundPayment $paymentCharge, $chargeId)
    {
        try {
           $response = $this->post("/v1/charges/" . $chargeId . "/refunds", $paymentCharge);
        } catch (ClientException $e) {
            // handle?
            throw $e;
        }

        return new CreateRefundResponse($response);
    }

}
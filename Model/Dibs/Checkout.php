<?php


namespace Dibs\EasyCheckout\Model\Dibs;


use Dibs\EasyCheckout\Model\Client\DTO\CreatePayment;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePaymentResponse;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerType;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\OrderItem;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\PaymentOrder;

class Checkout
{

    /** @var \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi */
    protected $paymentApi;

    public function __construct(
        \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi
    )
    {
        $this->paymentApi = $paymentApi;
    }

    public function initCheckout()
    {
        // TODO we need quote

    }


    /**
     * This function will create a new dibs payment.
     * The payment ID which is returned in the response will be added to the DIBS javascript API, to load the payment iframe.
     *
     * @throws \Exception
     * @return CreatePaymentResponse
     */
    protected function createNewDibsPayment()
    {

        $consumerType = new ConsumerType();
        $consumerType->setUseB2cOnly();

        $paymentCheckout = new CreatePaymentCheckout();
        $paymentCheckout->setCharge(true); // Default value = false, if set to true the transaction will be charged automatically after reservation have been accepted without calling the Charge API.
        $paymentCheckout->setConsumerType($consumerType);
        $paymentCheckout->setIntegrationType($paymentCheckout::INTEGRATION_TYPE_EMBEDDED);
        $paymentCheckout->setMerchantHandlesConsumerData(true); // WE Handle the customer data, i.e not attaching it in iframe!
        $paymentCheckout->setUrl("http://m230.localhost/onepage"); // TODO remove hardcode
        $paymentCheckout->setPublicDevice(true); //  Default value = false, if set to true the checkout will not load any user data
        $paymentCheckout->setTermsUrl("http://m230.localhost/terms"); // TODO load from settings
        $paymentCheckout->setMerchantHandlesShippingCost(false); // TODO set to true

        // all items
        $orderItems = [];

        // new item
        $orderItem = new OrderItem();
        $orderItem->setReference("sku111");
        $orderItem->setName("test produkt");
        $orderItem->setUnit("st");
        $orderItem->setQuantity(1);
        $orderItem->setTaxRate(25);
        $orderItem->setTaxAmount(25);
        $orderItem->setUnitPrice(100); // excl. tax price per item
        $orderItem->setNetTotalAmount(100); // excl. tax
        $orderItem->setGrossTotalAmount(125); // incl. tax

        // add to array
        $orderItems[] = $orderItem;

        // Todo generate from quote
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAmount(125);
        $paymentOrder->setCurrency("SEK");
        $paymentOrder->setReference("quote_id_1111");
        $paymentOrder->setItems($orderItems);

        $createPaymentRequest = new CreatePayment();
        $createPaymentRequest->setCheckout($paymentCheckout);
        $createPaymentRequest->setOrder($paymentOrder);
        return $this->paymentApi->createNewPayment($createPaymentRequest);
    }

}
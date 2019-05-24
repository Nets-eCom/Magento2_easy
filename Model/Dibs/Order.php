<?php


namespace Dibs\EasyCheckout\Model\Dibs;


use Dibs\EasyCheckout\Model\Client\Api\Payment;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePayment;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePaymentResponse;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerType;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\OrderItem;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\PaymentOrder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;

class Order
{

    /**
     * @var \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi
     */
    protected $paymentApi;


    public function __construct(
        \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi
    ) {
        $this->paymentApi = $paymentApi;
    }

    /** @var $_quote Quote */
    protected $_quote;

    /**
     * @throws LocalizedException
     * @return $this
     */
    public function assignQuote(Quote $quote,$validate = true, $initAdapter = true)
    {

        if ($validate) {
            if (!$quote->hasItems()) {
                throw new LocalizedException(__('Empty Cart'));
            }
            if ($quote->getHasError()) {
                throw new LocalizedException(__('Cart has errors, cannot checkout.'));
            }

            // TOdo we should check that the currency is valid (SEK, NOK, DKK)
        }

        $this->_quote = $quote;
        return $this;
    }


    /**
     * @param Quote $quote
     * @return string
     * @throws \Exception
     */
    public function initNewDibsCheckoutPaymentByQuote(\Magento\Quote\Model\Quote $quote)
    {
        // todo check if country is cvalid
        //  if(!$this->getOrderAdapter()->orderDataCountryIsValid($data,$country)){
        //     $this->reset();
        //}


        $paymentResponse = $this->createNewDibsPayment($quote);
        return $paymentResponse->getPaymentId();
    }


    // TODO!
    public function checkoutShouldBeUpdatedFromQuote($data, \Magento\Quote\Model\Quote $quote)
    {
        //$qSign = $this->getHelper()->getQuoteSignature($quote);
        // TODO compare signature

        // yes it should be updated from quote
        return false;
    }


    public function updateCheckoutPaymentById($paymentId)
    {

       // TODO make api call and update dibs payment!
        return $this;
    }


    /**
     * This function will create a new dibs payment.
     * The payment ID which is returned in the response will be added to the DIBS javascript API, to load the payment iframe.
     *
     * @throws \Exception
     * @return CreatePaymentResponse
     */
    protected function createNewDibsPayment(Quote $quote)
    {

        $consumerType = new ConsumerType();
        $consumerType->setUseB2cOnly();

        $paymentCheckout = new CreatePaymentCheckout();
        $paymentCheckout->setConsumerType($consumerType);
        $paymentCheckout->setIntegrationType($paymentCheckout::INTEGRATION_TYPE_EMBEDDED);
        $paymentCheckout->setUrl("http://m230.localhost/onepage"); // TODO remove hardcode
        $paymentCheckout->setTermsUrl("http://m230.localhost/terms"); // TODO load from settings


        $paymentCheckout->setCharge(true); // Default value = false, if set to true the transaction will be charged automatically after reservation have been accepted without calling the Charge API.
        $paymentCheckout->setMerchantHandlesConsumerData(false); // WE Handle the customer data, i.e not attaching it in iframe! when this is true we must attach consumer data
        $paymentCheckout->setMerchantHandlesShippingCost(false); // TODO set to true
        $paymentCheckout->setPublicDevice(true); //  Default value = false, if set to true the checkout will not load any user data

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


    /**
     * @return Payment
     */
    public function getPaymentApi()
    {
        return $this->paymentApi;
    }
}
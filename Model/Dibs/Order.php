<?php


namespace Dibs\EasyCheckout\Model\Dibs;


use Dibs\EasyCheckout\Model\Client\Api\Payment;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePayment;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePaymentResponse;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\Consumer;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerPhoneNumber;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerPrivatePerson;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerShippingAddress;
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

    /**
     * @var \Dibs\EasyCheckout\Helper\Data $helper
     */
    protected $helper;

    public function __construct(
        \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi,
        \Dibs\EasyCheckout\Helper\Data $helper
    ) {
        $this->helper = $helper;
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
        $privatePerson = new ConsumerPrivatePerson();
        $privatePerson->setFirstName("Fouäd");
        $privatePerson->setLastName("Ya");

        $phoneNumber = new ConsumerPhoneNumber();
        $phoneNumber->setPrefix("+46");
        $phoneNumber->setNumber("0739011773");

        $shippingAddress = new ConsumerShippingAddress();
        $shippingAddress->setAddressLine1("Klapperstensvägen 2d");
        $shippingAddress->setAddressLine2("");
        $shippingAddress->setCity("Jordbro");
        $shippingAddress->setPostalCode("13761");
        $shippingAddress->setCountry("SWE");

        $consumer = new Consumer();
        $consumer->setReference("1");
        $consumer->setEmail("fouad@nordicwebteam.se");
        $consumer->setPrivatePerson($privatePerson);
        $consumer->setPhoneNumber($phoneNumber);
        $consumer->setShippingAddress($shippingAddress);


        $consumerType = new ConsumerType();
        $consumerType->setUseB2cOnly();

        $paymentCheckout = new CreatePaymentCheckout();
        $paymentCheckout->setConsumer($consumer);
        $paymentCheckout->setConsumerType($consumerType);
        $paymentCheckout->setIntegrationType($paymentCheckout::INTEGRATION_TYPE_EMBEDDED);
        $paymentCheckout->setUrl($this->helper->getCheckoutUrl());
        $paymentCheckout->setTermsUrl("http://m230.localhost/terms"); // TODO load from settings


        $paymentCheckout->setCharge(true); // Default value = false, if set to true the transaction will be charged automatically after reservation have been accepted without calling the Charge API.
        $paymentCheckout->setMerchantHandlesConsumerData(true); // WE Handle the customer data, i.e not attaching it in iframe! when this is true we must attach consumer data
        $paymentCheckout->setMerchantHandlesShippingCost(false); // TODO set to true
        $paymentCheckout->setPublicDevice(false); //  Default value = false, if set to true the checkout will not load any user data




        // all items
        $orderItems = [];

        // new item
        $orderItem = new OrderItem();
        $orderItem->setReference("sku111");
        $orderItem->setName("test produkt");
        $orderItem->setUnit("st");
        $orderItem->setQuantity(1);
        $orderItem->setTaxRate(25);
        $orderItem->setTaxAmount($this->fixPrice(25));
        $orderItem->setUnitPrice($this->fixPrice(100)); // excl. tax price per item
        $orderItem->setNetTotalAmount($this->fixPrice(100)); // excl. tax
        $orderItem->setGrossTotalAmount($this->fixPrice(125)); // incl. tax

        // add to array
        $orderItems[] = $orderItem;

        // Todo generate from quote
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAmount($this->fixPrice(125));
        $paymentOrder->setCurrency("SEK");
        $paymentOrder->setReference("quote_id_1111");
        $paymentOrder->setItems($orderItems);


        $createPaymentRequest = new CreatePayment();
        $createPaymentRequest->setCheckout($paymentCheckout);
        $createPaymentRequest->setOrder($paymentOrder);
        return $this->paymentApi->createNewPayment($createPaymentRequest);
    }

    protected function fixPrice($price)
    {
        return $price * 100;
    }


    /**
     * @return Payment
     */
    public function getPaymentApi()
    {
        return $this->paymentApi;
    }
}
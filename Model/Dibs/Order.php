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
     * @var Items $items
     */
    protected $items;

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
        \Dibs\EasyCheckout\Helper\Data $helper,
        Items $itemsHandler
    ) {
        $this->helper = $helper;
        $this->items = $itemsHandler;
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
        // TODO handle this exception?
        $items = $this->items->generateOrderItemsFromQuote($quote);


        // todo check settings if b2c or/and b2b are accepted
        $consumerType = new ConsumerType();
        $consumerType->setUseB2bAndB2c();

        $paymentCheckout = new CreatePaymentCheckout();
        $paymentCheckout->setConsumerType($consumerType);
        $paymentCheckout->setIntegrationType($paymentCheckout::INTEGRATION_TYPE_EMBEDDED);
        $paymentCheckout->setUrl($this->helper->getCheckoutUrl());
        $paymentCheckout->setTermsUrl($this->helper->getTermsUrl());


        // Default value = false, if set to true the transaction will be charged automatically after reservation have been accepted without calling the Charge API.
        $paymentCheckout->setCharge(true);  // TODO use settings?

        // we let dibs handle customer data! customer will be able to fill in info in their iframe, and choose addresses
        $paymentCheckout->setMerchantHandlesConsumerData(false);

        // TODO set to true?
        $paymentCheckout->setMerchantHandlesShippingCost(false);

        //  Default value = false,
        // if set to true the checkout will not load any user data
        $paymentCheckout->setPublicDevice(false);


        // we generate the order here, amount and items
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAmount($this->fixPrice($quote->getGrandTotal()));
        $paymentOrder->setCurrency($quote->getCurrency()->getQuoteCurrencyCode());
        $paymentOrder->setReference("quote_id_" . $quote->getId());
        $paymentOrder->setItems($items);

        //
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
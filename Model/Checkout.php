<?php


namespace Dibs\EasyCheckout\Model;

use Magento\Framework\Exception\LocalizedException;

class Checkout extends \Magento\Checkout\Model\Type\Onepage
{

    protected $_paymentMethod = 'dibs_easycheckout';

    /** @var CheckoutContext $context */
    protected $context;

    protected $dibsPaymentOrderHandler;

    protected $_allowedCountries;


    public function setCheckoutContext(CheckoutContext $context)
    {
        $this->context = $context;
    }

    public function getHelper()
    {
        return $this->context->getHelper();
    }

    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * @param bool $reloadIfCurrencyChanged
     * @return $this
     * @throws CheckoutException
     * @throws LocalizedException
     */
    public function initCheckout($reloadIfCurrencyChanged = true)
    {
        if (!($this->context instanceof CheckoutContext)) {
            throw new \Exception("Context must set first!");
        }


        $quote  = $this->getQuote();
        $this->checkCart();

        //init checkout
        $customer = $this->getCustomerSession();
        if($customer->getId()) {
            //$this->_logger->info(__("Set customer %1",$customer->getId()));
            $quote->assignCustomer($customer->getCustomerDataObject()); //this will set also primary billing/shipping address as billing address
            //$quote->setCustomer($customer->getCustomerDataObject());
            //TODO: set customer address
        }

        $allowCountries = $this->getAllowedCountries(); //this is not null (it is checked into $this->checkCart())
        $blankAddress = $this->getBlankAddress($allowCountries[0]);

        $billingAddress  = $quote->getBillingAddress();
        if($quote->isVirtual()) {
            $shippingAddress = $billingAddress;
        } else {
            $shippingAddress = $quote->getShippingAddress();
        }

        if (!$shippingAddress->getCountryId()) {
            $this->_logger->info(__("No country set, change to %1",$allowCountries[0]));
            $this->changeCountry($allowCountries[0],$save = false);
        } elseif(!in_array($shippingAddress->getCountryId(),$allowCountries)) {
            $this->_logger->info(__("Wrong country set %1, change to %2",$shippingAddress->getCountryId(),$allowCountries[0]));
            $this->messageManager->addNotice(__("Klarna checkout is not available for %1, country was changed to %2.",$shippingAddress->getCountryId(),$allowCountries[0]));
            $this->changeCountry($allowCountries[0],$save = false);
        }

        if(!$billingAddress->getCountryId() || $billingAddress->getCountryId() != $shippingAddress->getCountryId()) {
            //$this->_logger->info(__("Billing country [%1] != shipping [%2]",$billingAddress->getCountryId(),$shippingAddress->getCountryId()));
            $this->changeCountry($shippingAddress->getCountryId(),$save = false);
        }


        $currencyChanged = $this->checkAndChangeCurrency();
        $payment = $quote->getPayment();


        //force payment method  to our payment method
        $paymentMethod     = $payment->getMethod();

        $shipPaymentMethod = $shippingAddress->getPaymentMethod();

        if(!$paymentMethod || !$shipPaymentMethod || $paymentMethod != $this->_paymentMethod || $shipPaymentMethod != $paymentMethod) {
            $payment->unsMethodInstance()->setMethod($this->_paymentMethod);
            $quote->setTotalsCollectedFlag(false);
            //if quote is virtual, shipping is set as billing (see above)
            //setCollectShippingRates because in onepagecheckout is affirmed that shipping rates could depends by payment method
            $shippingAddress->setPaymentMethod($payment->getMethod())->setCollectShippingRates(true);
        }


        //TODO: ADD MINIMUM AOUNT TEST here


        $method = $this->checkAndChangeShippingMethod();

        try {
            $quote->setTotalsCollectedFlag(false)->collectTotals()->save(); //REQUIRED (maybe shipping amount was changed)
        } catch (\Exception $e) {
            // do nothing
        }


        $billingAddress->save();
        $shippingAddress->save();

        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);
        $this->totalsCollector->collectQuoteTotals($quote);

        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        if($currencyChanged && $reloadIfCurrencyChanged) {
            //not needed
            $this->throwReloadException(__('Checkout was reloaded.'));
        }

        if($method === false) {
            throw new LocalizedException(__('No shipping method'));
        }

        return $this;
    }


    /**
     * @return bool
     * @throws CheckoutException
     */
    public function checkCart() {
        $quote = $this->getQuote();

        //@see OnePage::initCheckout
        if($quote->getIsMultiShipping()) {
            $quote->setIsMultiShipping(false)->removeAllAddresses();
        }

        if(!$this->getHelper()->isEnabled()) {
            $this->throwRedirectToCartException(__('The Dibs Easy Checkout is not enabled, please use an alternative checkout method.'));
        }

        if(!$this->getAllowedCountries()) {
            $this->throwRedirectToCartException(__('The Dibs Easy Checkout is NOT available (no allowed country), please use an alternative checkout method.'));
        }

        if (!$quote->hasItems()) {
            $this->throwRedirectToCartException(__('There are no items in your cart.'));
        }

        if ($quote->getHasError()) {
            $this->throwRedirectToCartException(__('The cart contains errors.'));
        }

        if (!$quote->validateMinimumAmount()) {
            $error =$this->getHelper()->getStoreConfig('sales/minimum_order/error_message');
            if (!$error) {
                $error = __('Subtotal must exceed minimum order amount.');
            }

            $this->throwRedirectToCartException($error);
        }
      
        return true;
    }


    public function checkAndChangeCurrency()
    {
        $quote  = $this->getQuote();
        $country    = $quote->getBillingAddress()->getCountryId();

        if (!$country) {
            throw new LocalizedException(__('Country is not set.')); // this shouldn't happen anyways
        }

        $quote->setTotalsCollectedFlag(false);
        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }

        return false;
    }


    public function changeCountry($country,$saveQuote = false) {

        $allowCountries = $this->getAllowedCountries();
        if(!$country || !in_array($country,$allowCountries)) {
            throw new LocalizedException(__('Invalid country (%1)',$country));
        }

        $blankAddress = $this->getBlankAddress($country);
        $quote        = $this->getQuote();

        $quote->getBillingAddress()->addData($blankAddress);
        if(!$quote->isVirtual()) {
            $quote->getShippingAddress()->addData($blankAddress)->setCollectShippingRates(true);
        }
        if($saveQuote) {
            $this->checkAndChangeCurrency();
            $quote->collectTotals()->save();
        }
    }



    public function getBlankAddress($country) {
        $blankAddress = array(
            'customer_address_id'=>0,
            'save_in_address_book'=>0,
            'same_as_billing'=>0,
            'street'=>'',
            'city'=>'',
            'postcode'=>'',
            'region_id'=>'',
            'country_id'=>$country
        );
        return $blankAddress;
    }


    public function checkAndChangeShippingMethod()
    {

        $quote = $this->getQuote();
        if($quote->isVirtual()) {
            return true;
        }

        $quote->collectTotals(); //this is need by shipping method with minimum amount

        $shipping = $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();
        $allRates = $shipping->getAllShippingRates();

        if(!count($allRates)) {
            return false;
        }

        $rates    = array();
        foreach($allRates as $rate) {
            $rates[$rate->getCode()] = $rate->getCode();
        }


        //test current
        $method = $shipping->getShippingMethod();
        if($method && isset($rates[$method])) {
            return $method;
        }

        // TODO remove this, customer must choose
        //test default
        $method = "flatrate_flatrate"; //$this->getHelper()->getShippingMethod();
        if($method && isset($rates[$method])) {
            $shipping->setShippingMethod($method);//->setCollectShippingRates(true);
            return $method;
        }

        $method = $allRates[0]->getCode();
        $shipping->setShippingMethod($method);//->setCollectShippingRates(true);
        return $method;

    }


    public function getAllowedCountries() {
        if(is_null($this->_allowedCountries)) {
            $this->_allowedCountries = ["SE", "DK", "NO"]; // todo get from settings
        }

        return $this->_allowedCountries;
    }


    /**
     * @return $this
     * @throws LocalizedException
     */
    public function initDibsCheckout()
    {
        $quote       = $this->getQuote();
        $dibsHandler = $this->getDibsPaymentHandler()->assignQuote($quote); // this will also validate the quote!

        // a signature is a md5 hashed value of the customer quote. Using this we can store the hash in session and compare the values
        $newSignature = $this->getHelper()->generateHashSignatureByQuote($quote);

        // check if we already have started a payment flow with dibs
        $paymentId = $this->getCheckoutSession()->getDibsPaymentId(); //check session for Klarna Order Uri
        if($paymentId) {
            try {

                // here we should check if we need to update the dibs payment!
                if ($dibsHandler->checkIfPaymentShouldBeUpdated($newSignature, $this->getCheckoutSession()->getDibsQuoteSignature())) {
                    // try to update dibs payment data
                    $dibsHandler->updateCheckoutPaymentByQuoteAndPaymentId($quote, $paymentId);

                    // Update new dibs quote signature!
                    $this->getCheckoutSession()->setDibsQuoteSignature($newSignature);
                }

            } catch(\Exception $e) {

                // If we couldn't update the dibs payment flow for any reason, we try to create an new one...

                // remove sessions
                $this->getCheckoutSession()->unsDibsPaymentId(); //remove payment id from session
                $this->getCheckoutSession()->unsDibsQuoteSignature(); //remove signature from session


                // this will create an api call to dibs and initiaze an new payment
                $newPaymentId = $dibsHandler->initNewDibsCheckoutPaymentByQuote($quote);

                //save the payment id and quote signature in checkout/session
                $this->getCheckoutSession()->setDibsPaymentId($newPaymentId);
                $this->getCheckoutSession()->setDibsQuoteSignature($newSignature);

                // We log this!
                $this->getLogger()->error("Trying to create a new payment because we could not Update Dibs Checkout Payment for ID: {$paymentId}, Error: {$e->getMessage()} (see exception.log)");
                $this->getLogger()->error($e);
            }

        } else {

            // this will create an api call to dibs and initiaze a new payment
            $paymentId = $dibsHandler->initNewDibsCheckoutPaymentByQuote($quote);

            //save klarna uri in checkout/session
            $this->getCheckoutSession()->setDibsPaymentId($paymentId);
            $this->getCheckoutSession()->setDibsQuoteSignature($newSignature);
        }


        return $this;
    }


    /**
     * @param $message
     * @throws CheckoutException
     */
    protected function throwRedirectToCartException($message)
    {
        throw new CheckoutException($message,'checkout/cart');
    }

    /**
     * @param $message
     * @throws CheckoutException
     */
    protected function throwReloadException($message)
    {
        throw new CheckoutException($message,'*/*');
    }


    /**
     * Get frontend checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     * @codeCoverageIgnore
     */
    public function getCheckoutSession()
    {
        return $this->_checkoutSession; //@see Onepage::__construct
    }

    /** @return \Dibs\EasyCheckout\Model\Dibs\Order */
    public function getDibsPaymentHandler()
    {
        return $this->context->getDibsOrderHandler();
    }


    //public function setDoNotMarkCartDirty() {   $this->_doNotMarkCartDirty = true; }
    //public function getDoNotMarkCartDirty() {   return $this->_doNotMarkCartDirty; }
}
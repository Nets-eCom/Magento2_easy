<?php

namespace Dibs\EasyCheckout\Model;

use Dibs\EasyCheckout\Model\Client\ClientException;
use Dibs\EasyCheckout\Model\Client\DTO\GetPaymentResponse;
use Dibs\EasyCheckout\Model\Client\DTO\PaymentResponseInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class Checkout extends \Magento\Checkout\Model\Type\Onepage
{
    protected $_paymentMethod = 'dibseasycheckout';

    /** @var CheckoutContext $context */
    protected $context;

    protected $dibsPaymentOrderHandler;

    protected $_allowedCountries;

    protected $_doNotMarkCartDirty  = false;

    /**
     * @param CheckoutContext $context
     */
    public function setCheckoutContext(CheckoutContext $context)
    {
        $this->context = $context;
    }

    /**
     * @return \Dibs\EasyCheckout\Helper\Data
     */
    public function getHelper()
    {
        return $this->context->getHelper();
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
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
            throw new \Exception("Dibs Context must be set first!");
        }

        $quote  = $this->getQuote();
        $this->checkCart();

        //init checkout
        $customer = $this->getCustomerSession();
        if ($customer->getId()) {
            $quote->assignCustomer($customer->getCustomerDataObject()); //this will set also primary billing/shipping address as billing address
            $quote->setCustomer($customer->getCustomerDataObject());
        }

        $allowCountries = $this->getAllowedCountries(); //this is not null (it is checked in $this->checkCart())

        $billingAddress  = $quote->getBillingAddress();
        if ($quote->isVirtual()) {
            $shippingAddress = $billingAddress;
        } else {
            $shippingAddress = $quote->getShippingAddress();
        }

        if (!$shippingAddress->getCountryId()) {
            $defaultCountryCode = $this->getDefaultCountryFromConfiguration($quote->getStoreId());
            $countryCode = $defaultCountryCode ?: $allowCountries[0];
            $this->_logger->info(__("No country set, change to %1", $countryCode));
            $this->changeCountry($countryCode, $save = false);
        } elseif (!in_array($shippingAddress->getCountryId(), $allowCountries)) {
            $defaultCountryCode = $this->getDefaultCountryFromConfiguration($quote->getStoreId());
            $countryCode = $defaultCountryCode ?: $allowCountries[0];

            $this->_logger->info(__("Wrong country set %1, change to %2", $shippingAddress->getCountryId(), $countryCode));
            $this->messageManager->addNoticeMessage(__("Nets Easy checkout is not available for %1, country was changed to %2.", $shippingAddress->getCountryId(), $countryCode));
            $this->changeCountry($countryCode, $save = false);
        }

        if (!$billingAddress->getCountryId() || $billingAddress->getCountryId() != $shippingAddress->getCountryId()) {
            $this->changeCountry($shippingAddress->getCountryId(), $save = false);
        }

        $currencyChanged = $this->checkAndChangeCurrency();
        $payment = $quote->getPayment();

        //force payment method  to our payment method
        $paymentMethod     = $payment->getMethod();

        $shipPaymentMethod = $shippingAddress->getPaymentMethod();

        if (!$paymentMethod || !$shipPaymentMethod || $paymentMethod != $this->_paymentMethod || $shipPaymentMethod != $paymentMethod) {
            $payment->unsMethodInstance()->setMethod($this->_paymentMethod);
            $quote->setTotalsCollectedFlag(false);
            //if quote is virtual, shipping is set as billing (see above)
            //setCollectShippingRates because in onepagecheckout is affirmed that shipping rates could depends by payment method
            $shippingAddress->setPaymentMethod($payment->getMethod())->setCollectShippingRates(true);
        }

        //TODO: ADD MINIMUM AOUNT TEST here

        $this->checkAndChangeShippingMethod();

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

        if ($currencyChanged && $reloadIfCurrencyChanged) {
            //not needed
            $this->throwReloadException(__('Checkout was reloaded.'));
        }

        /*
        if($method === false) {
            throw new LocalizedException(__('No shipping method'));
        }
        */

        return $this;
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    private function getDefaultCountryFromConfiguration($storeId)
    {
        /** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
        $scopeConfig = ObjectManager::getInstance()->create(
            \Magento\Framework\App\Config\ScopeConfigInterface::class
        );

        return $scopeConfig->getValue(
            'general/country/default',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return string
     */
    public function getQuoteSignature()
    {
        return $this->getHelper()->generateHashSignatureByQuote($this->getQuote());
    }

    /**
     * @return bool
     * @throws CheckoutException
     */
    public function checkCart()
    {
        $quote = $this->getQuote();

        //@see OnePage::initCheckout
        if ($quote->getIsMultiShipping()) {
            $quote->setIsMultiShipping(false)->removeAllAddresses();
        }

        if (!$this->getHelper()->isEnabled()) {
            $this->throwRedirectToCartException(__('The Nets Easy Checkout is not enabled, please use an alternative checkout method.'));
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

        if ($quote->getGrandTotal() <= 0) {
            $this->throwRedirectToCartException(__("Subtotal cannot be 0. Please choose another payment method."));
        }

        return true;
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
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

    /**
     * @return bool|mixed|string|void
     */
    public function checkAndChangeShippingMethod()
    {
        // If we have already set shipping method, then we're fine
        if ($this->getQuote()->getShippingAddress()->getShippingMethod()) {
            return;
        }

        $quote = $this->getQuote();
        if ($quote->isVirtual()) {
            return true;
        }

        //this is needed by shipping method with minimum amount
        $quote->collectTotals();

        $shipping = $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();
        $allRates = $shipping->getAllShippingRates();

        if (!count($allRates)) {
            return false;
        }

        $rates = [];
        foreach($allRates as $rate) {
            /** @var $rate Quote\Address\Rate  **/
            $rates[$rate->getCode()] = $rate->getCode();
        }

        // check if selected shipping method exists
        $method = $shipping->getShippingMethod();
        if($method && isset($rates[$method])) {
            return $method;
        }

        // check if default shipping method exists, use it then!
        $method = $this->getHelper()->getDefaultShippingMethod();
        if($method && isset($rates[$method])) {
            $shipping->setShippingMethod($method);
            return $method;
        }

        // fallback, use first shipping method found
        $rate = $allRates[0];
        $method = $rate->getCode();
        $shipping->setShippingMethod($method);
        return $method;

    }

    /**
     * @param $country
     * @param bool $saveQuote
     * @throws LocalizedException
     */
    public function changeCountry($country, $saveQuote = false)
    {
        $blankAddress = $this->getBlankAddress($country);
        $quote        = $this->getQuote();

        $quote->getBillingAddress()->addData($blankAddress);
        if (!$quote->isVirtual()) {
            $quote->getShippingAddress()->addData($blankAddress)->setCollectShippingRates(true);
        }
        if ($saveQuote) {
            $this->checkAndChangeCurrency();
            $quote->collectTotals()->save();
        }
    }

    /**
     * @param $country
     * @return array
     */
    public function getBlankAddress($country)
    {
        $blankAddress = [
            'customer_address_id'=>0,
            'save_in_address_book'=>0,
            'same_as_billing'=>0,
            'street'=>'',
            'city'=>'',
            'postcode'=>'',
            'region_id'=>'',
            'country_id'=>$country
        ];
        return $blankAddress;
    }

    /**
     * @return array
     */
    public function getAllowedCountries()
    {
        if (is_null($this->_allowedCountries)) {
            $this->_allowedCountries = $this->getHelper()->getCountries();
        }

        return array_keys($this->_allowedCountries);
    }

    /**
     * @param $integrationType string
     * @return PaymentResponseInterface
     * @throws LocalizedException
     */
    public function initDibsCheckout($integrationType)
    {
        $helper = $this->getHelper();
        $checkoutSession = $this->getCheckoutSession();
        $quote       = $this->getQuote();
        $dibsHandler = $this->getDibsPaymentHandler()->assignQuote($quote);

        $newSignature = $helper->generateHashSignatureByQuote($quote);
        $paymentId = $checkoutSession->getDibsPaymentId(); //check session for Dibs Payment Id
        if ($paymentId) {
            try {
                $payment = $this->getDibsPaymentHandler()->loadDibsPaymentById($paymentId);
                if ($dibsHandler->checkIfPaymentShouldBeUpdated($newSignature, $checkoutSession->getDibsQuoteSignature())) {
                    $dibsHandler->updateCheckoutPaymentByQuoteAndPaymentId($quote, $paymentId);
                    $checkoutSession->setDibsQuoteSignature($newSignature);
                }
            } catch (\Exception $e) {
                // If we couldn't update the dibs payment flow for any reason, we try to create an new one...
                // remove sessions
                $checkoutSession->unsDibsPaymentId();
                $checkoutSession->unsDibsQuoteSignature();

                // this will create an api call to dibs and initiaze an new payment
                $payment = $dibsHandler->initNewDibsCheckoutPaymentByQuote($quote, $integrationType);
                $newPaymentId = $payment->getPaymentId();

                //save the payment id and quote signature in checkout/session
                $checkoutSession->setDibsPaymentId($newPaymentId);

                // We log this!
                $this->getLogger()->error("Trying to create a new payment because we could not Update Dibs Checkout Payment for ID: {$paymentId}, Error: {$e->getMessage()} (see exception.log)");
                $this->getLogger()->error($e);
            }
        } else {
            // this will create an api call to dibs and initiaze a new payment
            $payment = $dibsHandler->initNewDibsCheckoutPaymentByQuote($quote, $integrationType);
            $paymentId = $payment->getPaymentId();

            //save dibs uri in checkout/session
            $checkoutSession->setDibsPaymentId($paymentId);
            $checkoutSession->setDibsQuoteSignature($newSignature);
        }

        return $payment;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        $countryCode = $this->getQuote()->getShippingAddress()->getCountryId();
        return $this->context->getDibsLocale()->getLocaleByCountryCode($countryCode);
    }

    /**
     * This vill be used in ajax calls
     * @throws LocalizedException
     */
    public function updateDibsPayment($paymentId)
    {
        $quote       = $this->getQuote();
        // This will also validate the quote!
        $dibsHandler = $this->getDibsPaymentHandler()->assignQuote($quote);
        $dibsHandler->updateCheckoutPaymentByQuoteAndPaymentId($quote, $paymentId);

        // Update new dibs quote signature!
        $newSignature = $this->getHelper()->generateHashSignatureByQuote($quote);
        $this->getCheckoutSession()->setDibsQuoteSignature($newSignature);
    }

    //Checkout ajax updates

    /**
     * Set shipping method to quote, if needed
     *
     * @param string $methodCode
     * @return void
     */
    public function updateShippingMethod($methodCode)
    {
        $quote = $this->getQuote();
        if ($quote->isVirtual()) {
            return;
        }
        $shippingAddress = $quote->getShippingAddress();
        if ($methodCode != $shippingAddress->getShippingMethod()) {
            $this->ignoreAddressValidation();
            $shippingAddress->setShippingMethod($methodCode)->setCollectShippingRates(true);
            $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
        }
    }

    /**
        // TODO
     * Update shipping address
     *
     * @param string $methodCode
     * @return void

    public function updateShippingAddress($data)
    {
        $quote = $this->getQuote();
        if($quote->isVirtual()) {
            return $this;
        }
        $addr = $this->getDibsPaymentHandler()->convertDibsShippingToMagentoAddress($data,$withEmpty = false);
        if(!$addr) return $this;

        $shippingAddress = $quote->getShippingAddress();


        $cnt = 0;
        foreach($addr as $field=>$value) {
            $kValue = trim(strtolower($value));
            $mValue = trim(strtolower((string)$shippingAddress->getData($field)));
            if($kValue != $mValue) {
                $shippingAddress->setData($field,$value);
                $cnt++;
            }
        }
        if($cnt) {
            $shippingAddress->setShouldIgnoreValidation(true)->setCollectShippingRates(true);
            $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
        }

    }
     */

    /**
     * Make sure addresses will be saved without validation errors
     *
     * @return void
     */
    private function ignoreAddressValidation()
    {
        $quote = $this->getQuote();
        $quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$quote->getIsVirtual()) {
            $quote->getShippingAddress()->setShouldIgnoreValidation(true);
        }
    }

    /**
     * @param $paymentId
     * @return true|void
     * @throws CheckoutException
     */
    public function tryToSaveDibsPayment($paymentId)
    {
        // TODO certain payment methods may be session independent

        $session = $this->getCheckoutSession();
        $quote = $this->getQuote();

        $checkoutPaymentId = $session->getDibsPaymentId();

        if (!$quote) {
            return $this->throwRedirectToCartException(__("Your session has expired. Quote missing."));
        }

        if (!$paymentId || !$checkoutPaymentId || ($paymentId != $checkoutPaymentId)) {
            $this->getLogger()->error("Invalid request");
            if (!$checkoutPaymentId) {
                $this->getLogger()->error("Save Order: No dibs checkout payment id in session.");
                return $this->throwRedirectToCartException(__("Your session has expired."));
            }

            if ($paymentId != $checkoutPaymentId) {
                return $this->getLogger()->error("Save Order: The session has expired or is wrong.");
            }

            return $this->getLogger()->error("Save Order: Invalid data.");
        }

        try {
            $payment = $this->getDibsPaymentHandler()->loadDibsPaymentById($paymentId);
        } catch (ClientException $e) {
            if ($e->getHttpStatusCode() == 404) {
                $this->getLogger()->error("Save Order: The dibs payment with ID: " . $paymentId . " was not found in dibs.");
                return $this->throwReloadException(__("Could not create an order. The payment was not found in dibs."));
            } else {
                $this->getLogger()->error("Save Order: Something went wrong when we tried to fetch the payment ID from Dibs. Http Status code: " . $e->getHttpStatusCode());
                $this->getLogger()->error("Error message:" . $e->getMessage());
                $this->getLogger()->debug($e->getResponseBody());

                return $this->throwReloadException(__("Could not create an order, please contact site admin. Dibs seems to be down!"));
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong.')
            );

            $this->getLogger()->error("Save Order: Something went wrong. Might have been the request parser. Payment ID: " . $checkoutPaymentId . "... Error message:" . $e->getMessage());
            return $this->throwReloadException(__("Something went wrong... Contact site admin."));
        }

        // an order should been created, lets wait 2 seconds to make sure it has been created!
        sleep(2);

        // LOAD ORDERS BY payment id! check if its has been created
        $orderCollection = $this->context->getOrderCollectionFactory()->create();

        /** @var \Magento\Sales\Model\Order $firstOrder */
        $firstOrder = $orderCollection
            ->addFieldToFilter('dibs_payment_id', ['eq' => $paymentId])
            ->getFirstItem();

        // by default (when embedded flow is used) we let nets handle customer data, if redirect flow is used then we handle it.

        // when the solution is hosted, the checkout url is not matching our checkout url!
        $weHandleConsumerData = false;
        $changeUrl = true;
        if ($this->getHelper()->getCheckoutUrl() !== $payment->getCheckoutUrl()) {
            $weHandleConsumerData = true;
            $changeUrl = false;
        }

        // HOWERE if quote is virtual, we let them handle consumer data, since we dont add these fields in our checkout!
        if ($quote->isVirtual()) {
            $weHandleConsumerData = false;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = null;
        $createOrder = true;
        if ($firstOrder && $firstOrder->getId()) {
            $order = $firstOrder;
            $createOrder = false;
        }

        if ($createOrder) {
            if ($payment->getOrderDetails()->getReference() !== $this->getDibsPaymentHandler()->generateReferenceByQuoteId($quote->getId())) {
                $this->getLogger()->error("Save Order: The customer Quote ID doesn't match with the dibs payment reference: " . $payment->getOrderDetails()->getReference());
                return $this->throwReloadException(__("Could not create an order. Invalid data. Contact admin."));
            }

            // In Swish there is no reserved amount?
            if ($payment->getSummary()->getReservedAmount() === null && $payment->getSummary()->getChargedAmount() === null) {
                $this->getLogger()->error("Save Order: Found no summary for the payment id: " . $payment->getPaymentId() . "... This must mean that they customer hasn't checked out yet!");
                return $this->throwReloadException(__("We could not create your order... No reserved or charged amount found. Payment id: %1", $payment->getPaymentId()));
            }

            try {
                $order = $this->placeOrder($payment, $quote, $weHandleConsumerData);
            } catch (\Exception $e) {
                $this->getLogger()->error("Could not place order for dibs payment with payment id: " . $payment->getPaymentId() . ", Quote ID:" . $quote->getId());
                $this->getLogger()->error("Error message:" . $e->getMessage());

                return $this->throwReloadException(__("We could not create your order. Please contact the site admin with this error and payment id: %1", $payment->getPaymentId()));
            }

            // Handle swish orders
            if ($this->isSwishPaymentValid($payment)) {
                $this->handleSwishOrder($payment, $order);
            }

            try {
                $this->updateMagentoPaymentReference($order, $paymentId, $changeUrl);
            } catch (\Exception $e) {
                $this->getLogger()->error(
                    "
                    Order created with ID: " . $order->getIncrementId() . ". 
                    But we could not update reference ID at dibs. Please handle it manually, it has id: quote_id_: " . $quote->getId() . "...  Dibs Payment ID: " . $payment->getPaymentId()
                );

                // lets ignore this and save it in logs! let customer see his/her order confirmation!
                $this->getLogger()->error("Error message:" . $e->getMessage());
            }
        }

        // clear old sessions
        $session->clearHelperData();
        $session->clearQuote()->clearStorage();

        // we set new sessions
        $session
            ->setLastQuoteId($order->getQuoteId())
            ->setLastSuccessQuoteId($order->getQuoteId())
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());

        return true;
    }

    /**
     * @param GetPaymentResponse $dibsPayment
     * @param Quote $quote
     * @param bool $weHandleConsumer
     * @param bool $setSessionOrderId
     * @return mixed
     * @throws \Exception
     */
    public function placeOrder(GetPaymentResponse $dibsPayment, Quote $quote, $weHandleConsumer = false, $setSessionOrderId = true)
    {
        $paymentMutex = $this->context->getPaymentMutex();
        if ($paymentMutex->test($dibsPayment->getPaymentId())) {
            throw new \Exception('Payment id already has been processing.');
        }

        //prevent observer to mark quote dirty, we will check here if quote was changed and, if yes, will redirect to checkout
        $this->setDoNotMarkCartDirty(true);

        /* // TODO
        try {
            $this->validateDibsPayment($dibsPayment,$quote);
        } catch (\Exception $e) {
            throw $e;
        }
        */

        //this will be saved in order
        $quote->setDibsPaymentId($dibsPayment->getPaymentId());

        // we use this country id if we fail converting dibs country id
        $fallbackCountryId = null;
        try {
            $fallbackCountryId = $quote->getShippingAddress()->getCountryId();
        } catch (\Exception $e) {
            // IGNORE
        }

        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        // $weHandleConsumer = !$quote->isVirtual() && redirect flow used, then we get address info from quote!
        if ($weHandleConsumer) {
            $shipping = $this->getDibsPaymentHandler()->convertAddressToArray($quote);
        } else {
            $shipping = $this->getDibsPaymentHandler()->convertDibsShippingToMagentoAddress($dibsPayment, $fallbackCountryId);
        }

        // WE only get shipping address from dibs!
        $billingAddress->addData($shipping);
        $billingAddress
            ->setCustomerAddressId(0)
            ->setSaveInAddressBook(0)
            ->setShouldIgnoreValidation(true);

        $shippingAddress->addData($shipping)
            ->setSameAsBilling(1)
            ->setCustomerAddressId(0)
            ->setSaveInAddressBook(0)
            ->setShouldIgnoreValidation(true);

        $quote->setCustomerEmail($billingAddress->getEmail());

        $customer      = $quote->getCustomer(); //this (customer_id) is set into self::init
        $createCustomer = false;

        if ($customer && $customer->getId()) {
            $quote->setCheckoutMethod(self::METHOD_CUSTOMER)
                ->setCustomerId($customer->getId())
                ->setCustomerEmail($customer->getEmail())
                ->setCustomerFirstname($customer->getFirstname())
                ->setCustomerLastname($customer->getLastname())
                ->setCustomerIsGuest(false);
        } else {
            //checkout method
            $quote->setCheckoutMethod(self::METHOD_GUEST)
                ->setCustomerId(null)
                ->setCustomerEmail($billingAddress->getEmail())
                ->setCustomerFirstname($billingAddress->getFirstname())
                ->setCustomerLastname($billingAddress->getLastname())
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);

            // register the customer, if its required, the customer will then be registered after order is placed
            if ($billingAddress->getEmail() && $this->getHelper()->registerCustomerOnCheckout()) {
                if (!$this->_customerEmailExists($billingAddress->getEmail(), $quote->getStore()->getWebsiteId())) {
                    $createCustomer = true;
                }
            }
        }

        //set payment
        $payment = $quote->getPayment();

        //force payment method
        if (!$payment->getMethod() || $payment->getMethod() != $this->_paymentMethod) {
            $payment->unsMethodInstance()->setMethod($this->_paymentMethod);
        }

        $paymentData = (new DataObject())
            ->setDibsPaymentId($dibsPayment->getPaymentId())
            ->setCountryId($shippingAddress->getCountryId())
            ->setDibsPaymentMethod($dibsPayment->getPaymentDetails()->getPaymentMethod());

        $quote->getPayment()->getMethodInstance()->assignData($paymentData);
        $quote->setDibsPaymentId($dibsPayment->getPaymentId()); //this is used by pushAction

        // we need to add invoice fee here to order if its enabled
        if ($this->getHelper()->useInvoiceFee()
            && $dibsPayment->getPaymentDetails()->getPaymentType() === "INVOICE"
            && $dibsPayment->getPaymentDetails()->getPaymentMethod() === "EasyInvoice"
        ) {
            $invoiceFee = $this->getHelper()->getInvoiceFee() * 1.25; // TODO remove hardcode!

            $quote->setDibsInvoiceFee($invoiceFee);
            // $quote->setGrandTotal($quote->getGrandTotal() + $invoiceFee);
            // $quote->setBaseGrandTotal($quote->getGrandTotal() + $invoiceFee);

            $quote->collectTotals();
        }

        //- do not recollect totals
        $quote->setTotalsCollectedFlag(true);

        //!
        // Now we create the order from the quote
        try {
            // Lock payment id to prevent double order creation
            $paymentMutex->lock($dibsPayment->getPaymentId());
            $order = $this->quoteManagement->submit($quote);
        } catch (\Exception $e) {
            $paymentMutex->release($dibsPayment->getPaymentId());
            $this->_logger->error($e);
            throw $e;
        }

        $this->_eventManager->dispatch(
            'checkout_type_onepage_save_order_after',
            ['order' => $order, 'quote' => $this->getQuote()]
        );

        if ($order->getCanSendNewEmailFlag()) {
            try {
                $this->orderSender->send($order);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }

        if ($setSessionOrderId) {
            // add order information to the session
            $this->_checkoutSession
                ->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());
        }

        $this->_eventManager->dispatch(
            'checkout_submit_all_after',
            [
                'order' => $order,
                'quote' => $this->getQuote()
            ]
        );

        if ($createCustomer) {
            //@see Magento\Checkout\Controller\Account\Create
            try {
                $this->context->getOrderCustomerManagement()->create($order->getId());
            } catch (\Exception $e) {
                $this->_logger->error(__("Order %1, cannot create customer [%2]: %3", $order->getIncrementId(), $order->getCustomerEmail(), $e->getMessage()));
                $this->_logger->critical($e);
            }
        }

        if ($order->getCustomerEmail() && $this->getHelper()->subscribeNewsletter($this->getQuote())) {
            try {
                //subscribe to newsletter
                $this->orderSubscribeToNewsLetter($order);
            } catch (\Exception $e) {
                $this->_logger->error("Cannot subscribe customer ({$order->getCustomerEmail()}) to the Newsletter: " . $e->getMessage());
            }
        }

        // Release the lock
        $paymentMutex->release($dibsPayment->getPaymentId());

        return $order;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $paymentId
     * @param $changeUrl bool
     * @return void
     * @throws ClientException
     */
    public function updateMagentoPaymentReference(\Magento\Sales\Model\Order $order, $paymentId, $changeUrl = true)
    {
        $this->getDibsPaymentHandler()->updateMagentoPaymentReference($order, $paymentId, $changeUrl);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     * @throws \Exception
     */
    protected function orderSubscribeToNewsLetter(\Magento\Sales\Model\Order $order)
    {
        $email = $order->getCustomerEmail();
        if (!$email) {
            return false;
        }

        $subscriber = $this->context->getSubscriber();
        $subscriber->loadByEmail($email);

        if ($subscriber->getId()) {
            return false;
        }

        return $subscriber->subscribe($email);
    }

    /**
     * @param $message
     * @throws CheckoutException
     */
    protected function throwRedirectToCartException($message)
    {
        throw new CheckoutException($message, 'checkout/cart');
    }

    /**
     * @param $message
     * @throws CheckoutException
     */
    protected function throwReloadException($message)
    {
        throw new CheckoutException($message, '*/*');
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

    /**
     * Handle Swish payment response
     *
     * @param GetPaymentResponse $paymentResponse
     *
     * @return bool
     */
    private function isSwishPaymentValid(GetPaymentResponse $paymentResponse)
    {
        return $this->context->getSwishHandler()->isSwishOrderValid($paymentResponse);
    }

    /**
     * @param GetPaymentResponse $paymentResponse
     * @param Order $order
     */
    public function handleSwishOrder(GetPaymentResponse $paymentResponse, Order $order)
    {
        $this->context->getSwishHandler()->saveOrder($paymentResponse, $order);
    }

    /**
     * @param $markDirty
     */
    public function setDoNotMarkCartDirty($markDirty)
    {
        $this->_doNotMarkCartDirty = (bool) $markDirty;
    }

    /**
     * @return bool
     */
    public function getDoNotMarkCartDirty()
    {
        return $this->_doNotMarkCartDirty;
    }
}

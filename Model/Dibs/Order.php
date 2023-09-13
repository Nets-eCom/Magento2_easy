<?php

namespace Dibs\EasyCheckout\Model\Dibs;

use Dibs\EasyCheckout\Model\Client\Api\Payment;
use Dibs\EasyCheckout\Model\Client\ClientException;
use Dibs\EasyCheckout\Model\Client\DTO\CancelPayment;
use Dibs\EasyCheckout\Model\Client\DTO\ChargePayment;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePayment;
use Dibs\EasyCheckout\Model\Client\DTO\CreatePaymentResponse;
use Dibs\EasyCheckout\Model\Client\DTO\GetPaymentResponse;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerType;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerTypeFactory;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentOrder;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentWebhook;
use Dibs\EasyCheckout\Model\Client\DTO\PaymentMethod;
use Dibs\EasyCheckout\Model\Client\DTO\RefundPayment;
use Dibs\EasyCheckout\Model\Client\DTO\UpdatePaymentCart;
use Dibs\EasyCheckout\Model\Client\DTO\UpdatePaymentReference;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order\Invoice;
use Magento\Store\Model\StoreManagerInterface;
use Dibs\EasyCheckout\Api\CheckoutFlow;

class Order {

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

    /**
     * @var ConsumerTypeFactory
     */
    protected $consumerTypeFactory;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;

    /** @var StoreManagerInterface */
    protected $storeManager;

    private \Dibs\EasyCheckout\Model\Quote\ConsumerDataProviderFactory $consumerDataProviderFactory;

    /**
     * Order constructor.
     *
     * @param Payment $paymentApi
     * @param \Dibs\EasyCheckout\Helper\Data $helper
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Dibs\EasyCheckout\Model\Quote\ConsumerDataProviderFactory $consumerDataProviderFactory
     * @param Items $itemsHandler
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
            \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi,
            \Dibs\EasyCheckout\Helper\Data $helper,
            ConsumerTypeFactory $consumerTypeFactory,
            \Magento\Directory\Model\CountryFactory $countryFactory,
            \Dibs\EasyCheckout\Model\Quote\ConsumerDataProviderFactory $consumerDataProviderFactory,
            Items $itemsHandler,
            StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->consumerTypeFactory = $consumerTypeFactory;
        $this->items = $itemsHandler;
        $this->paymentApi = $paymentApi;
        $this->_countryFactory = $countryFactory;
        $this->storeManager = $storeManager;
        $this->consumerDataProviderFactory = $consumerDataProviderFactory;
    }

    /** @var $_quote Quote */
    protected $_quote;

    /**
     * @throws LocalizedException
     * @return $this
     */
    public function assignQuote(Quote $quote, $validate = true) {
        if ($validate) {
            if (!$quote->hasItems()) {
                throw new LocalizedException(__('Empty Cart'));
            }
            if ($quote->getHasError()) {
                throw new LocalizedException(__('Cart has errors, cannot checkout.'));
            }
        }

        $this->_quote = $quote;
        return $this;
    }

    /**
     * @param Quote $quote
     * @param array $checkoutInfo
     * @return CreatePaymentResponse
     * @throws \Exception
     */
    public function initNewDibsCheckoutPaymentByQuote(\Magento\Quote\Model\Quote $quote, $checkoutInfo) {
        return $this->createNewDibsPayment($quote, $checkoutInfo);
    }

    /**
     * @param $newSignature
     * @param $currentSignature
     * @return bool
     */
    public function checkIfPaymentShouldBeUpdated($newSignature, $currentSignature) {

        // if the current signature is not set, then we must update payment
        if ($currentSignature == "" || $currentSignature == null) {
            return true;
        }

        // if the signatures doesn't match, it must mean that the quote has been changed!
        if ($newSignature != $currentSignature) {
            return true;
        }

        // nothing happened to the quote, we dont need to update payment at dibs!
        return false;
    }

    /**
     * @param Quote $quote
     * @param $paymentId
     * @throws \Exception
     */
    public function updateCheckoutPaymentByQuoteAndPaymentId(Quote $quote, $paymentId) {
        $items = $this->items->generateOrderItemsFromQuote($quote);

        $payment = new UpdatePaymentCart();
        $payment->setAmount($this->fixPrice($quote->getGrandTotal()));
        $payment->setItems($items);
        $payment->setShippingCostSpecified(true);

        $this->paymentApi->UpdatePaymentCart($payment, $paymentId);
    }

    /**
     * This function will create a new dibs payment.
     * The payment ID which is returned in the response will be added to the DIBS javascript API, to load the payment iframe.
     *
     * @param Quote $quote
     * @param array $checkoutInfo
     * @throws ClientException
     * @throws LocalizedException
     * @return CreatePaymentResponse
     */
    protected function createNewDibsPayment(Quote $quote, $checkoutInfo) {
        if (!$this->helper->getWebhookSecret()) {
            throw new LocalizedException(__("Webhook secret configuration missing!"));
        }

        $dibsAmount = $this->fixPrice($quote->getBaseGrandTotal());

        // let it throw exception, should be handled somewhere else
        $items = $this->items->generateOrderItemsFromQuote($quote);

        $consumerType = $this->generateConsumerType($quote);

        $integrationType = (isset($checkoutInfo['integrationType'])) ? $checkoutInfo['integrationType'] : '';

        $checkoutFlow = (isset($checkoutInfo['checkoutFlow'])) ? $checkoutInfo['checkoutFlow'] : '';
        $flowIsVanilla = $checkoutFlow === CheckoutFlow::FLOW_VANILLA;

        $paymentCheckout = new CreatePaymentCheckout();
        if ($this->helper->getSplitAddresses()) {
            $paymentCheckout->enableBillingAddress();
        }
        $paymentCheckout->setConsumerType($consumerType);
        $paymentCheckout->setIntegrationType($integrationType);

        $termsUrl = $this->helper->getTermsUrl();
        if (!empty($termsUrl)) {
            if (strlen($termsUrl) > 128) {
                $termsUrl = substr($termsUrl, 0, 128);
            }
        }
        $paymentCheckout->setTermsUrl($termsUrl);

        $electronicData = $this->helper->isElectronicData();
        $paymentCheckout->setElectronicData($electronicData);

        $privacyUrl = $this->helper->getPrivacyUrl();
        if (!empty($privacyUrl)) {
            if (strlen($privacyUrl) > 128) {
                $privacyUrl = substr($privacyUrl, 0, 128);
            }
        }
        $paymentCheckout->setPrivacyUrl($privacyUrl);

        if ($cancelUrl = $this->helper->getCancelUrl()) {
            $paymentCheckout->setCancelUrl($cancelUrl);
        }

        if ($integrationType === $paymentCheckout::INTEGRATION_TYPE_HOSTED || $flowIsVanilla) {
            // when we use hosted flow we set the url where customer should be redirected,
            // and we handle the consumer data
            if ($integrationType === $paymentCheckout::INTEGRATION_TYPE_HOSTED) {
                $baseUrl = $this->storeManager->getStore()->getBaseUrl();
                /* $paymentCheckout->setReturnUrl(
                  $this->helper->getCheckoutUrl('confirmOrder')
                  ); */
                $paymentCheckout->setReturnUrl($baseUrl . 'easycheckout/order/confirmOrder');
            }

            // If it's the vanilla checkout flow, we instead set checkout URL
            if ($flowIsVanilla) {
                //$paymentCheckout->setUrl($this->helper->getVanillaCheckoutUrl());
                $baseUrl = $this->storeManager->getStore()->getBaseUrl();
                $paymentCheckout->setUrl($baseUrl . 'easycheckout/order/confirmOrder');
            }

            if ($quote->isVirtual() && $this->helper->getCheckoutFlow() === "Vanilla") {
                // Nets to handle the merchant data when quote is virual for embeded flow...
                $paymentCheckout->setMerchantHandlesConsumerData(false);
            } else {
                try {
                    $handleCustomerData = $this->helper->doesHandleCustomerData();
                    $paymentCheckout->setMerchantHandlesConsumerData($handleCustomerData);
                    $consumerProvider = $this->consumerDataProviderFactory->create();
                    $paymentCheckout->setConsumer($consumerProvider->getFromQuote($quote));
                } catch (\Exception $e) {
                    $paymentCheckout->setMerchantHandlesConsumerData(false);
                }
            }
        } else {
            // when we use embedded, we set the url! and we allow nets to handle consumer data
            $paymentCheckout->setUrl($this->helper->getCheckoutUrl());
            $paymentCheckout->setMerchantHandlesConsumerData(false);
        }

        //If set to true the transaction will be charged automatically after reservation have been accepted without calling the Charge API.
        //If false we will call charge in capture online
        $charge = $this->helper->getCharge($quote->getStoreId());
        $paymentCheckout->setCharge($charge);

        // we let dibs handle customer data! customer will be able to fill in info in their iframe, and choose addresses
        $paymentCheckout->setMerchantHandlesShippingCost(true);
        //  Default value = false,
        // if set to true the checkout will not load any user data
        $paymentCheckout->setPublicDevice(false);

        // we generate the order here, amount and items
        $paymentOrder = new CreatePaymentOrder();

        //$paymentOrder->setCurrency($quote->getCurrency()->getQuoteCurrencyCode());
        $paymentOrder->setCurrency($this->storeManager->getStore()->getBaseCurrencyCode());
        $paymentOrder->setReference($this->generateReferenceByQuoteId($quote->getId()));
        $paymentOrder->setAmount((int) $dibsAmount);
        $paymentOrder->setItems($items);

        // create payment object
        $createPaymentRequest = new CreatePayment();
        $createPaymentRequest->setCheckout($paymentCheckout);
        $createPaymentRequest->setOrder($paymentOrder);

        // add invoice fee
        if ($this->helper->useInvoiceFee()) {
            $invoiceLabel = $this->helper->getInvoiceFeeLabel();
            $invoiceLabel = $invoiceLabel ? $invoiceLabel : __("Invoice Fee");
            $invoiceFee = $this->helper->getInvoiceFee();

            if ($invoiceFee > 0) {
                $feeItem = $this->items->generateInvoiceFeeItem($invoiceLabel, $invoiceFee, false);

                $paymentFee = new PaymentMethod();
                $paymentFee->setName("easyinvoice");
                $paymentFee->setFee($feeItem);

                $createPaymentRequest->setPaymentMethods([$paymentFee]);
            }
        }

        $webhookReservationCreated = new CreatePaymentWebhook();
        $webhookReservationCreated->setEventName(CreatePaymentWebhook::EVENT_PAYMENT_RESERVATION_CREATED);
        $webHookUrl = $this->helper->getWebHookCallbackUrl($webhookReservationCreated->getControllerName());
        $webhookReservationCreated->setUrl($webHookUrl);
        $webhooks = [$webhookReservationCreated];

        // We want to use the payment.checkout.completed webhook only with Hosted integration // changed after discussion
        //if ($integrationType === $paymentCheckout::INTEGRATION_TYPE_HOSTED) {
        $webhookCheckoutCompleted = new CreatePaymentWebhook();
        $webhookCheckoutCompleted->setEventName(CreatePaymentWebhook::EVENT_PAYMENT_CHECKOUT_COMPLETED);
        $webHookUrl = $this->helper->getWebHookCallbackUrl($webhookCheckoutCompleted->getControllerName());
        $webhookCheckoutCompleted->setUrl($webHookUrl);
        $webhooks[] = $webhookCheckoutCompleted;

//EVENT_PAYMENT_CHARGE_CREATED
        $webhookChargeCreated = new CreatePaymentWebhook();
        $webhookChargeCreated->setEventName(CreatePaymentWebhook::EVENT_PAYMENT_CHARGE_CREATED);
        $webHookChargeUrl = $this->helper->getWebHookCallbackUrl($webhookChargeCreated->getControllerName());
        $webhookChargeCreated->setUrl($webHookChargeUrl);
        $webhooks[] = $webhookChargeCreated;

        //EVENT_PAYMENT_NEW_CHARGE_CREATED
        $webhookNewChargeCreated = new CreatePaymentWebhook();
        $webhookNewChargeCreated->setEventName(CreatePaymentWebhook::EVENT_PAYMENT_NEW_CHARGE_CREATED);
        $webHookNewChargeUrl = $this->helper->getWebHookCallbackUrl($webhookNewChargeCreated->getControllerName());
        $webhookNewChargeCreated->setUrl($webHookNewChargeUrl);
        $webhooks[] = $webhookNewChargeCreated;

        //EVENT_PAYMENT_REFUND_INITIATED
        $webhookRefundInit = new CreatePaymentWebhook();
        $webhookRefundInit->setEventName(CreatePaymentWebhook::EVENT_PAYMENT_REFUND_INITIATED);
        $webHookRefundInitUrl = $this->helper->getWebHookCallbackUrl($webhookRefundInit->getControllerName());
        $webhookRefundInit->setUrl($webHookRefundInitUrl);
        $webhooks[] = $webhookRefundInit;

        //EVENT_PAYMENT_NEW_REFUND_INITIATED
        $webhookNewRefundInit = new CreatePaymentWebhook();
        $webhookNewRefundInit->setEventName(CreatePaymentWebhook::EVENT_PAYMENT_NEW_REFUND_INITIATED);
        $webHookNewRefundInitUrl = $this->helper->getWebHookCallbackUrl($webhookNewRefundInit->getControllerName());
        $webhookNewRefundInit->setUrl($webHookNewRefundInitUrl);
        $webhooks[] = $webhookNewRefundInit;

        //EVENT_PAYMENT_REFUND_COMPLETED
        $webhookRefundCompleted = new CreatePaymentWebhook();
        $webhookRefundCompleted->setEventName(CreatePaymentWebhook::EVENT_PAYMENT_REFUND_COMPLETED);
        $webHookRefundUrl = $this->helper->getWebHookCallbackUrl($webhookRefundCompleted->getControllerName());
        $webhookRefundCompleted->setUrl($webHookRefundUrl);
        $webhooks[] = $webhookRefundCompleted;

        //EVENT_PAYMENT_CANCEL_CREATED
        $webhookCancelCreated = new CreatePaymentWebhook();
        $webhookCancelCreated->setEventName(CreatePaymentWebhook::EVENT_PAYMENT_CANCEL_CREATED);
        $webHookCancelUrl = $this->helper->getWebHookCallbackUrl($webhookCancelCreated->getControllerName());
        $webhookCancelCreated->setUrl($webHookCancelUrl);
        $webhooks[] = $webhookCancelCreated;

        //}

        foreach ($webhooks as $webhook) {
            $webhook->setAuthorization($this->helper->getWebhookSecret());
        }

        $createPaymentRequest->setWebHooks($webhooks);

        return $this->paymentApi->createNewPayment($createPaymentRequest);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $paymentId
     * @return void
     * @throws ClientException
     */
    public function updateMagentoPaymentReference(\Magento\Sales\Model\Order $order, $paymentId) {
        $reference = new UpdatePaymentReference();
        $reference->setReference($order->getIncrementId());
        $reference->setCheckoutUrl($this->helper->getCheckoutUrl());
        $storeId = $order->getStoreId();
        if ($this->helper->getCheckoutFlow() === "HostedPaymentPage") {
            $payment = $this->paymentApi->getPayment($paymentId, $storeId);
            $checkoutUrl = $payment->getCheckoutUrl();
            $reference->setCheckoutUrl($checkoutUrl);
        }
        $this->paymentApi->UpdatePaymentReference($reference, $paymentId, $storeId);
    }

    /**
     * @param GetPaymentResponse $payment
     * @param null $countryIdFallback
     * @return array
     */
    public function convertDibsAddress(GetPaymentResponse $payment, $countryIdFallback = null, $isShipping = true) {
        if ($payment->getConsumer() === null) {
            return [];
        }

        $company = null;
        // if company name is set, then contact details are too
        if ($payment->getIsCompany()) {
            $companyObj = $payment->getConsumer()->getCompany();
            $contact = $companyObj->getContactDetails();
            $firstname = $contact->getFirstName();
            $lastName = $contact->getLastName();
            $company = $companyObj->getName();
            $phone = $contact->getPhoneNumber()->getPhoneNumber();
            $email = $contact->getEmail();
        } else {
            $private = $payment->getConsumer()->getPrivatePerson();
            $firstname = $private->getFirstName();
            $lastName = $private->getLastName();
            $phone = $private->getPhoneNumber()->getPhoneNumber();
            $email = $private->getEmail();
        }

        $address = $isShipping ? $payment->getConsumer()->getShippingAddress() : $payment->getConsumer()->getBillingAddress();

        $streets[] = $address->getAddressLine1();
        if ($address->getAddressLine2()) {
            $streets[] = $address->getAddressLine2();
        }

        $data = [
            'firstname' => $firstname,
            'lastname' => $lastName,
            'company' => $company,
            'telephone' => $phone,
            'email' => $email,
            'street' => $streets,
            'city' => $address->getCity(),
            'postcode' => $address->getPostalCode(),
        ];

        try {
            $countryId = $this->_countryFactory->create()->loadByCode($address->getCountry())->getId();
        } catch (\Exception $e) {
            $countryId = $countryIdFallback;
        }

        if ($countryId) {
            $data['country_id'] = $countryId;
        }

        return $data;
    }

    public function convertAddressToArray(Quote $quote) {
        $shipping = $quote->getShippingAddress();
        $email = "";
        if ($quote->getCustomerEmail()) {
            $email = $quote->getCustomerEmail();
        } elseif ($quote->getBillingAddress() && $quote->getBillingAddress()->getEmail()) {
            $email = $quote->getBillingAddress()->getEmail();
        } elseif ($shipping->getEmail()) {
            $email = $shipping->getEmail();
        }

        if (!$email) {
            throw new LocalizedException(__("E-mail address not found."));
        }

        $data = [
            'firstname' => $shipping->getFirstname(),
            'lastname' => $shipping->getLastname(),
            'company' => $shipping->getCompany(),
            'telephone' => $shipping->getTelephone(),
            'email' => $email,
            'street' => $shipping->getStreet(),
            'city' => $shipping->getCity(),
            'postcode' => $shipping->getPostcode(),
        ];

        return $data;
    }

    public function convertAddressToArrayBilling(Quote $quote) {
        //       $shipping = $quote->getShippingAddress();
        $billing = $quote->getBillingAddress();
        $email = "";
        if ($quote->getCustomerEmail()) {
            $email = $quote->getCustomerEmail();
        } elseif ($quote->getBillingAddress() && $quote->getBillingAddress()->getEmail()) {
            $email = $quote->getBillingAddress()->getEmail();
        } elseif ($billing->getEmail()) {
            $email = $billing->getEmail();
        }

        if (!$email) {
            throw new LocalizedException(__("E-mail address not found."));
        }

        $data = [
            'firstname' => $billing->getFirstname(),
            'lastname' => $billing->getLastname(),
            'company' => $billing->getCompany(),
            'telephone' => $billing->getTelephone(),
            'email' => $email,
            'street' => $billing->getStreet(),
            'city' => $billing->getCity(),
            'postcode' => $billing->getPostcode(),
            'country_id' => $billing->getCountry(),
        ];

        return $data;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $storeId
     * @throws ClientException
     * @throws LocalizedException
     */
    public function cancelDibsPayment(\Magento\Payment\Model\InfoInterface $payment, $storeId) {
        $paymentId = $payment->getAdditionalInformation('dibs_payment_id');
        if ($paymentId) {
            // we load the payment from dibs api instead, then we will get full amount!
            $payment = $this->loadDibsPaymentById($paymentId, $storeId);

            $paymentObj = new CancelPayment();
            $paymentObj->setAmount($payment->getSummary()->getReservedAmount());

            // cancel it now!
            $this->paymentApi->cancelPayment($paymentObj, $paymentId);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                            __('You need an dibs payment ID to void.')
            );
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @throws ClientException
     * @throws LocalizedException
     */
    public function captureDibsPayment(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $paymentId = $payment->getAdditionalInformation('dibs_payment_id');

        if ($paymentId) {

            /** @var Invoice $invoice */
            $invoice = $payment->getCapturedInvoice(); // we get this from Observer\PaymentCapture

            if (!$invoice) {
                throw new LocalizedException(__('Cannot capture online, no invoice set'));
            }

            // generate items
            $this->items->addDibsItemsByInvoice($invoice);

            // at this point we got VAT/Tax Rate from items above.
            if ($invoice->getDibsInvoiceFee()) {
                $this->items->addInvoiceFeeItem($this->helper->getInvoiceFeeLabel(), $invoice->getDibsInvoiceFee(), true);
            }

            // We validate the items before we send them to Dibs. This might throw an exception!
            $this->items->validateTotals($invoice->getGrandTotal());

            // now we have our items...
            $captureItems = $this->items->getCart();

            $paymentDetails = $this->paymentApi->getPayment($paymentId, $invoice->getStoreId());
            if($paymentDetails->GetPaymentDetails()->getPaymentMethod() == "EasyInvoice"){
              foreach($captureItems as $captureItem){
                $untiPrice = $captureItem->getGrossTotalAmount()/$captureItem->getQuantity();
                $captureItem->setTaxRate(0);
                $captureItem->setTaxAmount(0);
                $captureItem->setUnitPrice($untiPrice);
                //$captureItem->setUnitPrice();
                $captureItem->setNetTotalAmount($captureItem->getGrossTotalAmount());
              }
            }
            if ($this->helper->getCharge() && !empty($paymentDetails->getChargeDetails())) {
                $chargeId = $paymentDetails->getChargeDetails()->getChargeId();
            } else if ($paymentDetails->getPaymentDetails()->getPaymentType() == "A2A") {
                $chargeId = $paymentDetails->getChargeDetails()->getChargeId();
            } else if ($this->helper->canCapturePartial()) {
                if ($paymentDetails->getSummary()->getReservedAmount() == $paymentDetails->getSummary()->getChargedAmount()) {
                    $chargeId = $paymentDetails->getChargeDetails()->getChargeId();
                } else {
                    $paymentObj = new ChargePayment();
                    $paymentObj->setAmount($this->fixPrice($amount));
                    $paymentObj->setItems($captureItems);

                    // capture/charge it now!
                    $response = $this->paymentApi->chargePayment($paymentObj, $paymentId);
                    $chargeId = $response->getChargeId();
                }
            } else {
                $paymentObj = new ChargePayment();
                $paymentObj->setAmount($this->fixPrice($amount));
                $paymentObj->setItems($captureItems);

                // capture/charge it now!
                $response = $this->paymentApi->chargePayment($paymentObj, $paymentId);
                $chargeId = $response->getChargeId();
            }

            // save charge id, we need it later! if a refund will be made
            $payment->setAdditionalInformation('dibs_charge_id', $chargeId);
            $payment->setTransactionId($chargeId);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                            __('You need an dibs payment ID to capture.')
            );
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @throws ClientException
     * @throws LocalizedException
     */
    public function refundDibsPayment(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        //$chargeId = $payment->getAdditionalInformation('dibs_charge_id');
        $chargeId = $payment->getRefundTransactionId();
        if ($chargeId) {
            $creditMemo = $payment->getCreditMemo();
            $this->items->addDibsItemsByCreditMemo($creditMemo);

            // remove dibs invoice fee from amount
            if ($creditMemo->getDibsInvoiceFee()) {
                $invoiceFee = $this->items->generateInvoiceFeeItem($this->helper->getInvoiceFeeLabel(), $creditMemo->getDibsInvoiceFee(), true);

                $fee = ($invoiceFee->getGrossTotalAmount() / 100);

                if ($creditMemo->getAdjustmentNegative() && $fee != $creditMemo->getAdjustmentNegative()) {
                    throw new LocalizedException(__("The adjustment fee must match the Dibs Invoice Fee, if you don't want to refund the invoice fee."));
                }

                // we only add invoice fee to refund if adjustment fee isnt matching the invoice fee
                if ($creditMemo->getAdjustmentNegative() != $fee) {
                    $this->items->addToCart($invoiceFee);
                }
            } else {
                if ($creditMemo->getAdjustmentNegative() > 0) {
                    throw new LocalizedException(__("You can only add an adjustment fee that matches the Dibs Invoice Fee"));
                }
            }

            // We validate the items before we send them to Dibs. This might throw an exception!
            $this->items->validateTotals($creditMemo->getGrandTotal());

            $refundItems = $this->items->getCart();
            $amountToRefund = $this->fixPrice($amount);

            $paymentObj = new RefundPayment();
            $paymentObj->setAmount($amountToRefund);
            $paymentObj->setItems($refundItems);

            // refund now!
            $response = $this->paymentApi->refundPayment($paymentObj, $chargeId);

            try {
                // save refund id, just for debugging purposes
                $payment->setAdditionalInformation('dibs_refund_id', $response->getRefundId());
                $payment->setTransactionId($response->getRefundId());
            } catch (\Exception $e) {
                // do nothing we dont really  need this
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                            __('You need an dibs charge ID to refund.')
            );
        }
    }

    /**
     * @param $paymentId
     * @param $storeId
     * @return GetPaymentResponse
     * @throws ClientException
     */
    public function loadDibsPaymentById($paymentId, $storeId) {
        return $this->paymentApi->getPayment($paymentId, $storeId);
    }

    /**
     * @param $price
     * @return float|int
     */
    protected function fixPrice($price) {
        return (int) round($price * 100, 0);
    }

    /**
     * @return Payment
     */
    public function getPaymentApi() {
        return $this->paymentApi;
    }

    /**
     * @param $quoteId
     * @return string
     */
    public function generateReferenceByQuoteId($quoteId) {
        return "quote_id_" . $quoteId;
    }

    /**
     * Generate consumer types based on current config
     *
     * @param Quote $quote
     * @return ConsumerType
     */
    private function generateConsumerType($quote) {
        $consumerType = $this->consumerTypeFactory->create();

        // If we handle consumer data, consumer type is set based on customer address
        $weHandleConsumer = $this->helper->doesHandleCustomerData();
        if ($weHandleConsumer) {
            $isCompany = !empty($quote->getShippingAddress()->getCompany());
            if ($isCompany) {
                $consumerType->setUseB2bOnly();
                return $consumerType;
            }

            $consumerType->setUseB2cOnly();
            return $consumerType;
        }

        // If we don't handle customer data, use the configured settings
        $defaultConsumerType = $this->helper->getDefaultConsumerType();
        $consumerTypes = $this->helper->getConsumerTypes();

        // Default to B2C if no config detected
        if (!$defaultConsumerType || !$consumerTypes) {
            $consumerType->setUseB2cOnly();
            return $consumerType;
        }

        $consumerType->setDefault($defaultConsumerType);
        $consumerType->setSupportedTypes($consumerTypes);
        return $consumerType;
    }

}

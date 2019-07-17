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
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentOrder;
use Dibs\EasyCheckout\Model\Client\DTO\PaymentMethod;
use Dibs\EasyCheckout\Model\Client\DTO\RefundPayment;
use Dibs\EasyCheckout\Model\Client\DTO\UpdatePaymentCart;
use Dibs\EasyCheckout\Model\Client\DTO\UpdatePaymentReference;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order\Invoice;

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

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;

    public function __construct(
        \Dibs\EasyCheckout\Model\Client\Api\Payment $paymentApi,
        \Dibs\EasyCheckout\Helper\Data $helper,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        Items $itemsHandler
    ) {
        $this->helper = $helper;
        $this->items = $itemsHandler;
        $this->paymentApi = $paymentApi;
        $this->_countryFactory  = $countryFactory;
    }

    /** @var $_quote Quote */
    protected $_quote;

    /**
     * @throws LocalizedException
     * @return $this
     */
    public function assignQuote(Quote $quote, $validate = true)
    {
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
     * @return string
     * @throws \Exception
     */
    public function initNewDibsCheckoutPaymentByQuote(\Magento\Quote\Model\Quote $quote)
    {

        $paymentResponse = $this->createNewDibsPayment($quote);
        return $paymentResponse->getPaymentId();
    }

    /**
     * @param $newSignature
     * @param $currentSignature
     * @return bool
     */
    public function checkIfPaymentShouldBeUpdated($newSignature, $currentSignature)
    {

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
     * @return Update
     * @throws \Exception
     */
    public function updateCheckoutPaymentByQuoteAndPaymentId(Quote $quote, $paymentId)
    {
        $items = $this->items->generateOrderItemsFromQuote($quote);

        $payment = new UpdatePaymentCart();
        $payment->setAmount($this->fixPrice($quote->getGrandTotal()));
        $payment->setItems($items);

        $payment->setShippingCostSpecified(true);

        return $this->paymentApi->UpdatePaymentCart($payment, $paymentId);
    }

    /**
     * This function will create a new dibs payment.
     * The payment ID which is returned in the response will be added to the DIBS javascript API, to load the payment iframe.
     *
     * @param Quote $quote
     * @throws ClientException
     * @return CreatePaymentResponse
     */
    protected function createNewDibsPayment(Quote $quote)
    {
        $dibsAmount = $this->fixPrice($quote->getGrandTotal());

        // let it throw exception, should be handled somewhere else
        $items = $this->items->generateOrderItemsFromQuote($quote);

        // set consumer type(s)
        $defaultConsumerType = $this->helper->getDefaultConsumerType();
        $consumerTypes = $this->helper->getConsumerTypes();

        $consumerType = new ConsumerType();
        // if no settings are added, add B2C
        if (!$defaultConsumerType || !$consumerTypes) {
            $consumerType->setUseB2cOnly();
        } else {
            $consumerType->setDefault($defaultConsumerType);
            $consumerType->setSupportedTypes($consumerTypes);
        }

        $paymentCheckout = new CreatePaymentCheckout();
        $paymentCheckout->setConsumerType($consumerType);
        $paymentCheckout->setIntegrationType($paymentCheckout::INTEGRATION_TYPE_EMBEDDED);
        $paymentCheckout->setUrl($this->helper->getCheckoutUrl());
        $paymentCheckout->setTermsUrl($this->helper->getTermsUrl());

        // Default value = false, if set to true the transaction will be charged automatically after reservation have been accepted without calling the Charge API.
        // we will call charge in capture online instead! so we set it to false
        $paymentCheckout->setCharge(false);

        // we let dibs handle customer data! customer will be able to fill in info in their iframe, and choose addresses
        $paymentCheckout->setMerchantHandlesConsumerData(false);
        $paymentCheckout->setMerchantHandlesShippingCost(true);
        //  Default value = false,
        // if set to true the checkout will not load any user data
        $paymentCheckout->setPublicDevice(false);

        // we generate the order here, amount and items
        $paymentOrder = new CreatePaymentOrder();

        $paymentOrder->setCurrency($quote->getCurrency()->getQuoteCurrencyCode());
        $paymentOrder->setReference($this->generateReferenceByQuoteId($quote->getId()));
        $paymentOrder->setAmount($dibsAmount);
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

        return $this->paymentApi->createNewPayment($createPaymentRequest);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $paymentId
     * @return void
     * @throws ClientException
     */
    public function updateMagentoPaymentReference(\Magento\Sales\Model\Order $order, $paymentId)
    {
        $reference = new UpdatePaymentReference();
        $reference->setReference($order->getIncrementId());
        $reference->setCheckoutUrl($this->helper->getCheckoutUrl());
        $this->paymentApi->UpdatePaymentReference($reference, $paymentId);
    }

    /**
     * @param GetPaymentResponse $payment
     * @param null $countryIdFallback
     * @return array
     */
    public function convertDibsShippingToMagentoAddress(GetPaymentResponse $payment, $countryIdFallback = null)
    {
        if ($payment->getConsumer() === null) {
            return [];
        }

        $company = null;
        // if company name is set, then contact details are too
        if ($payment->getIsCompany()) {
            $companyObj = $payment->getConsumer()->getCompany();
            $contact = $companyObj->getContactDetails();
            $firstname =$contact->getFirstName();
            $lastName = $contact->getLastName();
            $company = $companyObj->getName();
            $phone = $contact->getPhoneNumber()->getPhoneNumber();
            $email = $contact->getEmail();
        } else {
            $private = $payment->getConsumer()->getPrivatePerson();
            $firstname =$private->getFirstName();
            $lastName = $private->getLastName();
            $phone = $private->getPhoneNumber()->getPhoneNumber();
            $email = $private->getEmail();
        }

        $address = $payment->getConsumer()->getShippingAddress();
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

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @throws ClientException
     * @throws LocalizedException
     */
    public function cancelDibsPayment(\Magento\Payment\Model\InfoInterface $payment)
    {
        $paymentId = $payment->getAdditionalInformation('dibs_payment_id');
        if ($paymentId) {

            // we load the payment from dibs api instead, then we will get full amount!
            $payment = $this->loadDibsPaymentById($paymentId);

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
    public function captureDibsPayment(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
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

            $paymentObj = new ChargePayment();
            $paymentObj->setAmount($this->fixPrice($amount));
            $paymentObj->setItems($captureItems);

            // capture/charge it now!
            $response = $this->paymentApi->chargePayment($paymentObj, $paymentId);

            // save charge id, we need it later! if a refund will be made
            $payment->setAdditionalInformation('dibs_charge_id', $response->getChargeId());
            $payment->setTransactionId($response->getChargeId());
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
    public function refundDibsPayment(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $chargeId = $payment->getAdditionalInformation('dibs_charge_id');
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
     * @return GetPaymentResponse
     * @throws ClientException
     */
    public function loadDibsPaymentById($paymentId)
    {
        return $this->paymentApi->getPayment($paymentId);
    }

    /**
     * @param $price
     * @return float|int
     */
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

    /**
     * @param $quoteId
     * @return string
     */
    public function generateReferenceByQuoteId($quoteId)
    {
        return "quote_id_" . $quoteId;
    }
}

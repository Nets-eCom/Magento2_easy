<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;

use Dibs\EasyCheckout\Model\Client\DTO\Payment\ConsumerPhoneNumber;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\GetConsumerCompany;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\GetConsumerCompanyContactDetails;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\GetConsumerPrivatePerson;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\GetConsumerShippingAddress;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\GetPaymentCardDetails;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\GetPaymentConsumer;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\GetPaymentDetails;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\GetPaymentInvoiceDetails;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\GetPaymentOrder;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\GetPaymentSummary;

class GetPaymentResponse implements PaymentResponseInterface
{
    private $isCompany = false;

    /** @var GetPaymentOrder $orderDetails */
    protected $orderDetails;

    /** @var GetPaymentConsumer $consumer */
    protected $consumer;

    /** @var GetPaymentSummary $summary */
    protected $summary;

    /** @var GetPaymentDetails $paymentDetails */
    protected $paymentDetails;

    /** @var string $paymentId */
    protected $paymentId;

    /** @var $checkoutUrl string */
    protected $checkoutUrl;

    /**
     * If response is not empty, we fill the values from the API.
     *
     * GetPaymentResponse constructor.
     * @param $response string
     */
    public function __construct($response = "")
    {
        if ($response !== "") {
            $data = json_decode($response);
            $p = $data->payment;
            $orderDetails = new GetPaymentOrder();
            $summary = new GetPaymentSummary();
            $paymentDetails = new GetPaymentDetails();
            $consumer = new GetPaymentConsumer();

            if (!empty((array)$p->orderDetails)) {
                $orderDetails->setReference($this->_get($p->orderDetails, 'reference'));
                $orderDetails->setAmount($this->_get($p->orderDetails, 'amount'));
                $orderDetails->setCurrency($this->_get($p->orderDetails, 'currency'));
            }

            if (!empty((array)$p->summary)) {
                $summary->setReservedAmount($this->_get($p->summary, 'reservedAmount'));
                $summary->setChargedAmount($this->_get($p->summary, 'chargedAmount'));
            }

            if (!empty((array)$p->paymentDetails) && isset($p->paymentDetails->paymentMethod)) {
                $paymentDetails->setPaymentMethod($this->_get($p->paymentDetails, 'paymentMethod'));
                $paymentDetails->setPaymentType($this->_get($p->paymentDetails, 'paymentType'));

                // if card!
                if (!empty((array)$p->paymentDetails->cardDetails)) {
                    $cardDetails = new GetPaymentCardDetails();
                    $cardDetails->setExpiryDate($this->_get($p->paymentDetails->cardDetails, 'expiryDate'));
                    $cardDetails->setMaskedPan($this->_get($p->paymentDetails->cardDetails, 'maskedPan'));

                    $paymentDetails->setCardDetails($cardDetails);
                }

                // if invoice!
                if (!empty((array)$p->paymentDetails->invoiceDetails)) {
                    $invoiceDetails = new GetPaymentInvoiceDetails();
                    $invoiceDetails->setDueDate($this->_get($p->paymentDetails->invoiceDetails, 'dueDate'));
                    $invoiceDetails->setInvoiceNumber($this->_get($p->paymentDetails->invoiceDetails, 'invoiceNumber'));
                    $invoiceDetails->setOcr($this->_get($p->paymentDetails->invoiceDetails, 'ocr'));
                    $invoiceDetails->setPdfLink($this->_get($p->paymentDetails->invoiceDetails, 'pdfLink'));

                    $paymentDetails->setInvoiceDetails($invoiceDetails);
                }
            }

            if (!empty((array)$p->consumer)) {
                if (!empty((array)$p->consumer->shippingAddress)) {
                    $s = $p->consumer->shippingAddress;
                    $shippingAddress = new GetConsumerShippingAddress();

                    $shippingAddress->setPostalCode($this->_get($s, 'postalCode'));
                    $shippingAddress->setCountry($this->_get($s, 'country'));
                    $shippingAddress->setCity($this->_get($s, 'city'));
                    $shippingAddress->setReceiverLine($this->_get($s, 'receiverLine'));
                    $shippingAddress->setAddressLine1($this->_get($s, 'addressLine1'));
                    if (isset($s->addressLine2)) {
                        $shippingAddress->setAddressLine2($s->addressLine2);
                    }

                    $consumer->setShippingAddress($shippingAddress);
                }

                if (!empty((array)$p->consumer->billingAddress)) {
                    $s = $p->consumer->billingAddress;
                    $billingAddress = new GetConsumerShippingAddress();

                    $billingAddress->setPostalCode($this->_get($s, 'postalCode'));
                    $billingAddress->setCountry($this->_get($s, 'country'));
                    $billingAddress->setCity($this->_get($s, 'city'));
                    $billingAddress->setReceiverLine($this->_get($s, 'receiverLine'));
                    $billingAddress->setAddressLine1($this->_get($s, 'addressLine1'));
                    if (isset($s->addressLine2)) {
                        $billingAddress->setAddressLine2($s->addressLine2);
                    }

                    $consumer->setBillingAddress($billingAddress);
                }

                if (!empty((array)$p->consumer->privatePerson)) {
                    $priv = $p->consumer->privatePerson;
                    $pp = new GetConsumerPrivatePerson();

                    //
                    $phoneNumber = new ConsumerPhoneNumber();
                    $phoneNumber->setNumber($this->_get($priv->phoneNumber, 'number'));
                    $phoneNumber->setPrefix($this->_get($priv->phoneNumber, 'prefix'));

                    $pp->setLastName($this->_get($priv, 'lastName'));
                    $pp->setFirstName($this->_get($priv, 'firstName'));
                    $pp->setEmail($this->_get($priv, 'email'));
                    $pp->setPhoneNumber($phoneNumber);

                    $consumer->setPrivatePerson($pp);

                    $this->isCompany = false;
                }

                // consumer->company seems never to be empty, since it contains empty objects of contactDetails, so we check if company name is empty
                if (!empty($p->consumer->company->name)) {
                    $this->isCompany = true;
                    $org = $p->consumer->company;
                    $company = new GetConsumerCompany();
                    $contact = new GetConsumerCompanyContactDetails();

                    if (!empty((array)$org->contactDetails->phoneNumber)) {
                        $phone = new ConsumerPhoneNumber();
                        $phone->setNumber($this->_get($org->contactDetails->phoneNumber, 'number'));
                        $phone->setPrefix($this->_get($org->contactDetails->phoneNumber, 'prefix'));
                        $contact->setPhoneNumber($phone);
                    }

                    if (!empty((array)$org->contactDetails) && isset($org->contactDetails->firstName)) {
                        $contact->setFirstName($this->_get($org->contactDetails, 'firstName'));
                        $contact->setLastName($this->_get($org->contactDetails, 'lastName'));
                        $contact->setEmail($this->_get($org->contactDetails, 'email'));
                    }

                    // add data to company
                    $company->setContactDetails($contact);
                    $company->setName($this->_get($org, 'name'));
                    $company->setRegistrationNumber($this->_get($org, 'registrationNumber'));

                    // add company to consumer
                    $consumer->setCompany($company);
                }
            }

            $url = "";
            if (!empty((array)$p->checkout)) {
                $url = $this->_get($p->checkout, 'url');
            }

            // we set all data!
            $this->setPaymentId($p->paymentId);
            $this->setOrderDetails($orderDetails);
            $this->setSummary($summary);
            $this->setPaymentDetails($paymentDetails);
            $this->setConsumer($consumer);
            $this->setCheckoutUrl($url);
        }
    }

    /**
     * @return string
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @param string $paymentId
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    /**
     * @return GetPaymentOrder
     */
    public function getOrderDetails()
    {
        return $this->orderDetails;
    }

    /**
     * @param GetPaymentOrder $orderDetails
     * @return GetPaymentResponse
     */
    public function setOrderDetails($orderDetails)
    {
        $this->orderDetails = $orderDetails;
        return $this;
    }

    /**
     * @return GetPaymentDetails
     */
    public function getPaymentDetails()
    {
        return $this->paymentDetails;
    }

    /**
     * @param GetPaymentDetails $paymentDetails
     * @return GetPaymentResponse
     */
    public function setPaymentDetails($paymentDetails)
    {
        $this->paymentDetails = $paymentDetails;
        return $this;
    }

    /**
     * @return GetPaymentSummary
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * @param GetPaymentSummary $summary
     * @return GetPaymentResponse
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;
        return $this;
    }

    /**
     * @return GetPaymentConsumer
     */
    public function getConsumer()
    {
        return $this->consumer;
    }

    /**
     * @param GetPaymentConsumer $consumer
     * @return GetPaymentResponse
     */
    public function setConsumer($consumer)
    {
        $this->consumer = $consumer;
        return $this;
    }

    /**
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->checkoutUrl;
    }

    /**
     * @param string $checkoutUrl
     * @return GetPaymentResponse
     */
    public function setCheckoutUrl($checkoutUrl)
    {
        $this->checkoutUrl = $checkoutUrl;
        return $this;
    }



    public function getIsCompany()
    {
        return $this->isCompany;
    }

    protected function _get($obj, $key)
    {
        $arr = (array)$obj;
        if (isset($arr[$key])) {
            return $arr[$key];
        }

        return null;
    }
}

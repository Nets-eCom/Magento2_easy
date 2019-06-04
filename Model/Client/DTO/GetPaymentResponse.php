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

class GetPaymentResponse
{

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
                $orderDetails->setReference($p->orderDetails->reference);
                $orderDetails->setAmount($p->orderDetails->amount);
                $orderDetails->setCurrency($p->orderDetails->currency);
            }

            if (!empty((array)$p->summary)) {
                $summary->setReservedAmount($p->summary->reservedAmount);
            }

            if (!empty((array)$p->paymentDetails)) {
                $paymentDetails->setPaymentMethod($p->paymentDetails->paymentMethod);
                $paymentDetails->setPaymentType($p->paymentDetails->paymentType);

                // if card!
                if (!empty((array)$p->paymentDetails->cardDetails)) {
                    $cardDetails = new GetPaymentCardDetails();
                    $cardDetails->setExpiryDate($p->paymentDetails->cardDetails->expiryDate);
                    $cardDetails->setMaskedPan($p->paymentDetails->cardDetails->maskedPan);

                    $paymentDetails->setCardDetails($cardDetails);
                }

                // if invoice!
                if (!empty((array)$p->paymentDetails->invoiceDetails)) {
                    $invoiceDetails = new GetPaymentInvoiceDetails();
                    $invoiceDetails->setDueDate($p->paymentDetails->invoiceDetails->dueDate);
                    $invoiceDetails->setInvoiceNumber($p->paymentDetails->invoiceDetails->invoiceNumber);
                    $invoiceDetails->setOcr($p->paymentDetails->invoiceDetails->ocr);
                    $invoiceDetails->setPdfLink($p->paymentDetails->invoiceDetails->pdfLink);

                    $paymentDetails->setInvoiceDetails($invoiceDetails);
                }
            }

            if (!empty((array)$p->consumer)) {
                if (!empty((array)$p->consumer->shippingAddress)) {
                    $s = $p->consumer->shippingAddress;
                    $shippingAddress = new GetConsumerShippingAddress();

                    $shippingAddress->setPostalCode($s->postalCode);
                    $shippingAddress->setCountry($s->country);
                    $shippingAddress->setCity($s->city);
                    $shippingAddress->setReceiverLine($s->receiverLine);
                    $shippingAddress->setAddressLine1($s->addressLine1);
                    $shippingAddress->setAddressLine2($s->addressLine2);

                    $consumer->setShippingAddress($shippingAddress);
                }

                if (!empty((array)$p->consumer->privatePerson)) {
                    $priv = $p->consumer->privatePerson;
                    $pp = new GetConsumerPrivatePerson();

                    //
                    $phoneNumber = new ConsumerPhoneNumber();
                    $phoneNumber->setNumber($priv->phoneNumber->number);
                    $phoneNumber->setPrefix($priv->phoneNumber->prefix);

                    $pp->setLastName($priv->lastName);
                    $pp->setFirstName($priv->firstName);
                    $pp->setEmail($priv->email);
                    $pp->setPhoneNumber($phoneNumber);

                    $consumer->setPrivatePerson($pp);
                }

                // consumer->company seems never to be empty, since it contains empty objects of contactDetails, so we check if company name is empty
                if (!empty($p->consumer->company->name)) {
                    $org = $p->consumer->company;
                    $company = new GetConsumerCompany();
                    $contact = new GetConsumerCompanyContactDetails();

                    if (!empty((array)$org->contactDetails->phoneNumber)) {
                        $phone = new ConsumerPhoneNumber();
                        $phone->setNumber($org->contactDetails->phoneNumber->number);
                        $phone->setPrefix($org->contactDetails->phoneNumber->prefix);
                        $contact->setPhoneNumber($phone);
                    }

                    if (!empty((array)$org->contactDetails)){
                        $contact->setFirstName($org->contactDetails->firstName);
                        $contact->setLastName($org->contactDetails->lastName);
                        $contact->setEmail($org->contactDetails->email);
                    }

                    // add data to company
                    $company->setContactDetails($contact);
                    $company->setName($org->name);
                    $company->setRegistrationNumber($org->registrationNumber);


                    // add company to consumer
                    $consumer->setCompany($company);
                }
            }

            // we set all data!
            $this->setPaymentId($p->paymentId);
            $this->setOrderDetails($orderDetails);
            $this->setSummary($summary);
            $this->setPaymentDetails($paymentDetails);
            $this->setConsumer($consumer);
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




}
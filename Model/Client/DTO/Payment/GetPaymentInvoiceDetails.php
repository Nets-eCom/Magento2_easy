<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

class GetPaymentInvoiceDetails
{

    /** @var string $dueDate */
   protected $dueDate;

    /** @var string $invoiceNumber */
    protected $invoiceNumber;

    /** @var string $ocr */
    protected $ocr;

    /** @var string $pdfLink */
    protected $pdfLink;

    /**
     * @return string
     */
    public function getDueDate()
    {
        return $this->dueDate;
    }

    /**
     * @param string $dueDate
     * @return GetPaymentInvoiceDetails
     */
    public function setDueDate($dueDate)
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    /**
     * @param string $invoiceNumber
     * @return GetPaymentInvoiceDetails
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getOcr()
    {
        return $this->ocr;
    }

    /**
     * @param string $ocr
     * @return GetPaymentInvoiceDetails
     */
    public function setOcr($ocr)
    {
        $this->ocr = $ocr;
        return $this;
    }

    /**
     * @return string
     */
    public function getPdfLink()
    {
        return $this->pdfLink;
    }

    /**
     * @param string $pdfLink
     * @return GetPaymentInvoiceDetails
     */
    public function setPdfLink($pdfLink)
    {
        $this->pdfLink = $pdfLink;
        return $this;
    }


}
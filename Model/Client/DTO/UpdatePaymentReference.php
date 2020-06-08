<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;

class UpdatePaymentReference extends AbstractRequest
{

    /**
     * Required
     * Magento Order ID
     * @var string $reference
     */
    protected $reference;

    /**
     * Required???
     * @var $checkoutUrl string
     */
    protected $checkoutUrl;

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     * @return UpdatePaymentReference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
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
     * @return UpdatePaymentReference
     */
    public function setCheckoutUrl($checkoutUrl)
    {
        $this->checkoutUrl = $checkoutUrl;
        return $this;
    }



    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        $data = [
            'reference' => $this->getReference(),
        ];

        if ($this->getCheckoutUrl()) {
            $data['checkoutUrl'] = $this->getCheckoutUrl();
        }
        return $data;
    }


}

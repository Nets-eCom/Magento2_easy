<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;


class CreateRefundResponse
{

    /** @var string $refundId */
    protected $refundId;

    /**
     * CreateRefundResponse constructor.
     * @param $response string
     */
    public function __construct($response = "")
    {
        if ($response !== "") {
            $data = json_decode($response);
            $this->setRefundId($data->refundId);
        }
    }

    /**
     * @return string
     */
    public function getRefundId()
    {
        return $this->refundId;
    }

    /**
     * @param string $refundId
     * @return CreateRefundResponse
     */
    public function setRefundId($refundId)
    {
        $this->refundId = $refundId;
        return $this;
    }


}
<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;

class ReportingResponse
{

    /** @var string $status */
    protected $status;
    
    /** @var string $data */
    protected $data;


    /**
     * ReportingResponse constructor.
     * @param $response string
     */
    public function __construct($response = "")
    {
        if ($response !== "") {
            $dataReponse = json_decode($response);
            $this->setStatus($dataReponse->status);
            $this->setData($dataReponse->data);
        }
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $status
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

}

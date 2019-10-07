<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;

use Dibs\EasyCheckout\Model\Client\DTO\AbstractRequest;

class ConsumerType extends AbstractRequest
{

    const BUSSINESS_TO_CUSTOMER = "B2C";
    const BUSSINESS_TO_BUSSINESS = "B2B";

    protected $acceptedValues = [
        self::BUSSINESS_TO_CUSTOMER,
        self::BUSSINESS_TO_BUSSINESS
    ];

    /**
     * Required
     * B2C and/or B2B
     * @var string[] $supportedTypes
     */
    protected $supportedTypes;

    /**
     * Sets the default consumer type
     * @var string $default
     */
    protected $default;

    /**
     * @return string[]
     */
    public function getSupportedTypes()
    {
        return $this->supportedTypes;
    }

    /**
     * @param string[] $supportedTypes
     * @return ConsumerType
     */
    public function setSupportedTypes($supportedTypes)
    {
        $this->supportedTypes = $supportedTypes;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param string $default
     * @return ConsumerType
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }


    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function toArray()
    {
        // validate data
        $this->validate();

        return [
            'default' => $this->getDefault(),
            'supportedTypes' => $this->getSupportedTypes(),
        ];
    }

    /**
     * @throws \Exception
     * @return void
     */
    protected function validate()
    {
        if (empty($this->getSupportedTypes())) {
            throw new \Exception("consumer type, supportedTypes must be specified");
        }

        foreach ($this->getSupportedTypes() as $type) {
            if (!in_array($type, $this->acceptedValues)) {
                throw new \Exception("consumer type, array item of supportedTypes must be of B2C or B2B");
            }
        }

        if (!in_array($this->getDefault(), $this->acceptedValues)) {
            throw new \Exception("consumer type, default type must be of B2C or B2B");
        }
    }

    public function setUseB2cOnly()
    {
        $this->default = self::BUSSINESS_TO_CUSTOMER;
        $this->supportedTypes = [
          self::BUSSINESS_TO_CUSTOMER
        ];
    }

    public function setUseB2bOnly()
    {
        $this->default = self::BUSSINESS_TO_BUSSINESS;
        $this->supportedTypes = [
            self::BUSSINESS_TO_BUSSINESS
        ];
    }

    public function setUseB2bAndB2c($default = self::BUSSINESS_TO_CUSTOMER)
    {
        $this->default = $default;
        $this->supportedTypes = [
            self::BUSSINESS_TO_CUSTOMER,
            self::BUSSINESS_TO_BUSSINESS
        ];
    }

}
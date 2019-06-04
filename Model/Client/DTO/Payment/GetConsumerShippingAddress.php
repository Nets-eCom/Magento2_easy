<?php
namespace Dibs\EasyCheckout\Model\Client\DTO\Payment;
class GetConsumerShippingAddress extends ConsumerShippingAddress
{


    /** @var string $receiverLine */
    protected $receiverLine;

    /**
     * @return string
     */
    public function getReceiverLine()
    {
        return $this->receiverLine;
    }

    /**
     * @param string $receiverLine
     * @return GetConsumerShippingAddress
     */
    public function setReceiverLine($receiverLine)
    {
        $this->receiverLine = $receiverLine;
        return $this;
    }



}
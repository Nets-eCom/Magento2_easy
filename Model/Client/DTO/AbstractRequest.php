<?php
namespace Dibs\EasyCheckout\Model\Client\DTO;



abstract class AbstractRequest
{
    // do stuff
    public abstract function toJSON();
    public abstract function toArray();
}
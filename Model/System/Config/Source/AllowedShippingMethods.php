<?php
namespace Dibs\EasyCheckout\Model\System\Config\Source;

class AllowedShippingMethods extends \Magento\Shipping\Model\Config\Source\Allmethods implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of active carriers.
     *
     * @param bool $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect=false)
    {
        $options = parent::toOptionArray(true);
        if($isMultiselect) {
            //remove first option (empty one)
            array_pop($options);
        }

        return $options;
    }
}
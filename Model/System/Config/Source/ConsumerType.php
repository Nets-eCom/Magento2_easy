<?php


namespace Dibs\EasyCheckout\Model\System\Config\Source;

class ConsumerType implements \Magento\Framework\Option\ArrayInterface
{


    public function toOptionArray($isMultiselect=false)
    {
    
        $options = array();
        if(!$isMultiselect) {
            $options[] = array('value'=>'', 'label'=> '');
        }

        $options[] = array(
               'value' => 'B2C',
               'label' => __('B2C')
            );
        $options[] = array(
               'value' => 'B2B',
               'label' => __('B2B')
        );

        return $options;
    }
}

<?php

namespace Nexi\Checkout\Model\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Nexi\Checkout\Model\Recurring\TotalConfigProvider;

class Attributes extends AbstractModifier
{
    private ArrayManager $arrayManager;

    private TotalConfigProvider $totalConfigProvider;

    /**
     * @param ArrayManager $arrayManager
     * @param TotalConfigProvider $totalConfigProvider
     */
    public function __construct(
        ArrayManager        $arrayManager,
        TotalConfigProvider $totalConfigProvider
    ) {
        $this->arrayManager        = $arrayManager;
        $this->totalConfigProvider = $totalConfigProvider;
    }

    /**
     * ModifyData
     *
     * @param array $data
     *
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * ModifyMeta.
     *
     * @param array $meta
     *
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        if (isset($meta['product-details']['children']['container_recurring_payment_schedule'])) {
            $attribute = 'recurring_payment_schedule';
            $path = $this->arrayManager->findPath($attribute, $meta, null, 'children');

            if (!$this->totalConfigProvider->isRecurringPaymentEnabled()) {
                $meta = $this->arrayManager->set(
                    "{$path}/arguments/data/config/visible",
                    $meta,
                    false
                );
            } else {
                $meta = $this->arrayManager->set(
                    "{$path}/arguments/data/config/visible",
                    $meta,
                    true
                );
            }
        }

        return $meta;
    }
}

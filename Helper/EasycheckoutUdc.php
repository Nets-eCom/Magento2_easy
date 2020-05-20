<?php

namespace Dibs\EasyCheckout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class EasycheckoutUdc extends AbstractHelper
{
    /**
     * Is shipping block should be replaced
     *
     * @return bool
     */
    public function isShippingBlockReplaced()
    {
        return $this->getConfig('dibs_easycheckout/layout/replace_shipping_mediastrategi_udc') == 1;
    }

    /**
     * Get internal config
     *
     * @param string $path
     * @param int|null [$storeId = null]
     * @return mixed
     */
    private function getConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }
}

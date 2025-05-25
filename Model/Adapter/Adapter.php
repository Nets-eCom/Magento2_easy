<?php

namespace Nexi\Checkout\Model\Adapter;

use Magento\Framework\Module\ModuleListInterface;
use Nexi\Checkout\Gateway\Config\Config;

class Adapter
{
    /**
     * @var string MODULE_CODE
     */
    const MODULE_CODE   = 'Nexi_Checkout';
    const CC_VAULT_CODE = 'nexi_cc_vault';


    /**
     * Adapter constructor.
     *
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        private ModuleListInterface $moduleList
    ) {
    }

    public function initNexiMerchantClient()
    {
        return true;
    }

    /**
     * @return string module version in format x.x.x
     */
    private function getExtensionVersion()
    {
        return $this->moduleList->getOne(self::MODULE_CODE)['setup_version'];
    }
}

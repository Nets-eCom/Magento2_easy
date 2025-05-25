<?php

namespace Nexi\Checkout\Model\Card;

use Magento\Framework\App\Config\ScopeConfigInterface;

class VaultConfig
{
    private const VAULT_FOR_NEXI_PATH = 'payment/nexi_cc_vault/active';
    private const NEXI_SHOW_STORED_CARDS = 'payment/nexi_cc_vault/show_stored_cards';

    /**
     * VaultConfig constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Returns is CC_Vault for cards is enabled for Nexi.
     *
     * @return bool
     */
    public function isVaultForPaytralEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::VAULT_FOR_NEXI_PATH);
    }

    /**
     * Returns is stored cards are displayed on checkout page.
     *
     * @return bool
     */
    public function isShowStoredCards(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::NEXI_SHOW_STORED_CARDS);
    }
}

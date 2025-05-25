<?php

namespace Nexi\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\StoreManagerInterface;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Card\VaultConfig;
use Nexi\Checkout\Model\ApplePay\ApplePayDataProvider;
use Nexi\Checkout\Model\Ui\DataProvider\PaymentProvidersData;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    private $methodCodes = [
        Config::CODE,
        Config::CC_VAULT_CODE
    ];

    /**
     * @var \Magento\Payment\Model\MethodInterface[]
     */
    private $methods;

    /**
     * ConfigProvider constructor
     *
     * @param PaymentHelper $paymentHelper
     * @param Session $checkoutSession
     * @param Config $gatewayConfig
     * @param StoreManagerInterface $storeManager
     * @param PaymentProvidersData $paymentProvidersData
     * @param VaultConfig $vaultConfig
     * @param ApplePayDataProvider $applePayDataProvider
     * @throws LocalizedException
     */
    public function __construct(
        PaymentHelper         $paymentHelper,
        private Session               $checkoutSession,
        private Config                $gatewayConfig,
        private StoreManagerInterface $storeManager,
        private PaymentProvidersData  $paymentProvidersData,
        private VaultConfig $vaultConfig,
        private ApplePayDataProvider $applePayDataProvider
    ) {
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * GetConfig function
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $config = [];
        $status = $this->gatewayConfig->isActive($storeId);

        if (!$status) {
            return $config;
        }
        try {
            $groupData = $this->paymentProvidersData->getAllPaymentMethods();
            $scheduledMethod = [];

            if (array_key_exists('creditcard', $this->paymentProvidersData
                ->handlePaymentProviderGroupData($groupData['groups']))) {
                $scheduledMethod[] = $this->paymentProvidersData
                    ->handlePaymentProviderGroupData($groupData['groups'])['creditcard'];
            }

            if ($this->applePayDataProvider->canApplePay()) {
                $groupData['groups'] = $this->applePayDataProvider->addApplePayPaymentMethod($groupData['groups']);
            }

            $config = [
                'payment' => [
                    Config::CODE => [
                        'instructions' => $this->gatewayConfig->getInstructions(),
                        'skip_method_selection' => $this->gatewayConfig->getSkipBankSelection(),
                        'payment_redirect_url' => $this->gatewayConfig->getPaymentRedirectUrl(),
                        'payment_template' => $this->gatewayConfig->getPaymentTemplate(),
                        'method_groups' => array_values($this->paymentProvidersData
                            ->handlePaymentProviderGroupData($groupData['groups'])),
                        'scheduled_method_group' => array_values($scheduledMethod),
                        'payment_terms' => $groupData['terms'],
                        'payment_method_styles' => $this->paymentProvidersData->wrapPaymentMethodStyles($storeId),
                        'addcard_redirect_url' => $this->gatewayConfig->getAddCardRedirectUrl(),
                        'pay_and_addcard_redirect_url' => $this->gatewayConfig->getPayAndAddCardRedirectUrl(),
                        'credit_card_providers_ids' => array_shift($scheduledMethod)['providers'] ?? [],
                        'token_payment_redirect_url' => $this->gatewayConfig->getTokenPaymentRedirectUrl(),
                        'default_success_page_url' => $this->gatewayConfig->getDefaultSuccessPageUrl(),
                        'is_vault_for_nexi' => $this->vaultConfig->isVaultForNexiEnabled(),
                        'is_show_stored_cards' => $this->vaultConfig->isShowStoredCards(),
                        'is_new_ui_enabled' => $this->gatewayConfig->isNewUiEnabled($storeId)
                    ]
                ]
            ];
            //Get images for payment groups
            foreach ($groupData['groups'] as $group) {
                $groupId = $group['id'];
                $groupImage = $group['svg'];
                $config['payment'][Config::CODE]['image'][$groupId] = '';
                if ($groupImage) {
                    $config['payment'][Config::CODE]['image'][$groupId] = $groupImage;
                }
            }
        } catch (\Exception $e) {
            $config['payment'][Config::CODE]['success'] = 0;

            return $config;
        }
        if ($this->checkoutSession->getData('nexi_previous_error')) {
            $config['payment'][Config::CODE]['previous_error'] = $this->checkoutSession
                ->getData('nexi_previous_error', 1);
        } elseif ($this->checkoutSession->getData('nexi_previous_success')) {
            $config['payment'][Config::CODE]['previous_success'] = $this->checkoutSession
                ->getData('nexi_previous_success', 1);
        }
        $config['payment'][Config::CODE]['success'] = 1;

        return $config;
    }
}

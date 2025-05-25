<?php

namespace Nexi\Checkout\Model\Ui\DataProvider;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Nexi\Checkout\Exceptions\CheckoutException;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Logger\NexiLogger;
use Psr\Log\LoggerInterface;

class PaymentProvidersData
{
    private const CREDITCARD_GROUP_ID = 'creditcard';
    public const  ID_INCREMENT_SEPARATOR = '__';
    public const  ID_CARD_TYPE_SEPARATOR = '__';

    /**
     * PaymentProvidersData constructor.
     *
     * @param Session $checkoutSession
     * @param LoggerInterface $log
     * @param CommandManagerPoolInterface $commandManagerPool
     * @param Config $gatewayConfig
     * @param NexiLogger $nexiLogger
     */
    public function __construct(
        private Session                     $checkoutSession,
        private LoggerInterface             $log,
        private CommandManagerPoolInterface $commandManagerPool,
        private Config                      $gatewayConfig,
        private NexiLogger              $nexiLogger
    ) {
    }

    /**
     * Get all payment methods and groups with order total value
     *
     * @return mixed|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAllPaymentMethods()
    {
        $orderValue = $this->checkoutSession->getQuote()->getGrandTotal() ?: 0;

        $commandExecutor = $this->commandManagerPool->get('nexi');
        $response = $commandExecutor->executeByCode(
            'method_provider',
            null,
            ['amount' => $orderValue]
        );

        $errorMsg = $response['error'];

        if (isset($errorMsg)) {
            $this->log->error(
                'Error occurred during providing payment methods: '
                . $errorMsg
            );
            $this->nexiLogger->logData(\Monolog\Logger::ERROR, $errorMsg);
            throw new CheckoutException(__($errorMsg));
        }

        return $response["data"];
    }

    /**
     * Create payment page styles from the values entered in Nexi configuration.
     *
     * @param string $storeId
     *
     * @return string
     */
    public function wrapPaymentMethodStyles($storeId)
    {
        if ($this->gatewayConfig->isNewUiEnabled()) {
            $styles = '.nexi-group-collapsible{ background-color: #ffffff; margin-top:1%; margin-bottom:2%;}';
            $styles .= '.nexi-group-collapsible.active{ background-color: #ffffff;}';
            $styles .= '.nexi-group-collapsible span{ color: #323232;}';
            $styles .= '.nexi-group-collapsible li{ color: #323232}';
            $styles .= '.nexi-group-collapsible.active span{ color: #000000;}';
            $styles .= '.nexi-group-collapsible.active li{ color: #000000}';
            $styles .= '.nexi-group-collapsible:hover:not(.active) {background-color: #ffffff}';
            $styles .= '.nexi-payment-methods .nexi-payment-method.active{ border: 2px solid '
                . $this->gatewayConfig->getPaymentMethodHighlightColorNewUi($storeId) . ';border-width:2px;}';
            $styles .= '.nexi-payment-methods .nexi-stored-token.active{ border-color:'
                . $this->gatewayConfig->getPaymentMethodHighlightColorNewUi($storeId) . ';border-width:2px;}';
            $styles .= '.nexi-payment-methods .nexi-payment-method:hover,
        .nexi-payment-methods .nexi-payment-method:not(.active):hover { border: 2px solid '
                . $this->gatewayConfig->getPaymentMethodHoverHighlightNewUi($storeId) . '}';
            $styles .= '.nexi-stored-token:hover { border: 2px solid '
                . $this->gatewayConfig->getPaymentMethodHoverHighlightNewUi($storeId) . '}';
            $styles .= '.nexi-store-card-button:hover { border: 2px solid '
                . $this->gatewayConfig->getPaymentMethodHoverHighlightNewUi($storeId) . ';}';
            $styles .= '.nexi-store-card-login-button:hover { border: 2px solid '
                . $this->gatewayConfig->getPaymentMethodHoverHighlightNewUi($storeId) . ';}';
            $styles .= $this->gatewayConfig->getAdditionalCss($storeId);
        } else {
            $styles = '.nexi-group-collapsible{ background-color:'
                . $this->gatewayConfig->getPaymentGroupBgColor($storeId) . '; margin-top:1%; margin-bottom:2%;}';
            $styles .= '.nexi-group-collapsible.active{ background-color:'
                . $this->gatewayConfig->getPaymentGroupHighlightBgColor($storeId) . ';}';
            $styles .= '.nexi-group-collapsible span{ color:'
                . $this->gatewayConfig->getPaymentGroupTextColor($storeId) . ';}';
            $styles .= '.nexi-group-collapsible li{ color:'
                . $this->gatewayConfig->getPaymentGroupTextColor($storeId) . '}';
            $styles .= '.nexi-group-collapsible.active span{ color:'
                . $this->gatewayConfig->getPaymentGroupHighlightTextColor($storeId) . ';}';
            $styles .= '.nexi-group-collapsible.active li{ color:'
                . $this->gatewayConfig->getPaymentGroupHighlightTextColor($storeId) . '}';
            $styles .= '.nexi-group-collapsible:hover:not(.active) {background-color:'
                . $this->gatewayConfig->getPaymentGroupHoverColor() . '}';
            $styles .= '.nexi-payment-methods .nexi-payment-method.active{ border: 2px solid '
                . $this->gatewayConfig->getPaymentMethodHighlightColor($storeId) . ';border-width:2px;}';
            $styles .= '.nexi-payment-methods .nexi-stored-token.active{ border-color:'
                . $this->gatewayConfig->getPaymentMethodHighlightColor($storeId) . ';border-width:2px;}';
            $styles .= '.nexi-payment-methods .nexi-payment-method:hover,
        .nexi-payment-methods .nexi-payment-method:not(.active):hover { border: 2px solid '
                . $this->gatewayConfig->getPaymentMethodHoverHighlight($storeId) . ';}';
            $styles .= '.nexi-stored-token:hover { border: 2px solid '
                . $this->gatewayConfig->getPaymentMethodHoverHighlight($storeId) . '}';
            $styles .= '.nexi-store-card-button:hover { border: 2px solid '
                . $this->gatewayConfig->getPaymentMethodHoverHighlight($storeId) . ';}';
            $styles .= '.nexi-store-card-login-button:hover { border: 2px solid '
                . $this->gatewayConfig->getPaymentMethodHoverHighlight($storeId) . ';}';
            $styles .= $this->gatewayConfig->getAdditionalCss($storeId);
        }

        return $styles;
    }

    /**
     * Create array for payment providers and groups containing unique method id
     *
     * @param array $responseData
     *
     * @return array
     */
    public function handlePaymentProviderGroupData($responseData)
    {
        $allMethods = [];
        $allGroups = [];
        foreach ($responseData as $group) {
            $allGroups[$group['id']] = [
                'id' => $group['id'],
                'name' => $group['name'],
                'icon' => $group['icon']
            ];

            foreach ($group['providers'] as $provider) {
                $allMethods[] = $provider;
            }
        }
        foreach ($allGroups as $key => $group) {
            if ($group['id'] == 'creditcard') {
                $allGroups[$key]["can_tokenize"] = true;
                $allGroups[$key]["tokens"] = $this->gatewayConfig->getCustomerTokens();
            } else {
                $allGroups[$key]["can_tokenize"] = false;
                $allGroups[$key]["tokens"] = false;
            }

            $allGroups[$key]['providers'] = $this->addProviderDataToGroup($allMethods, $group['id']);
        }
        return $allGroups;
    }

    /**
     * Add payment method data to group
     *
     * @param array $responseData
     * @param string $groupId
     *
     * @return array
     */
    private function addProviderDataToGroup($responseData, $groupId)
    {
        $methods = [];
        $i = 1;

        foreach ($responseData as $key => $method) {
            if ($method->getGroup() == $groupId) {
                $id = $groupId === self::CREDITCARD_GROUP_ID ? $method->getId()
                    . self::ID_INCREMENT_SEPARATOR
                    . strtolower($method->getName()) : $method->getId();
                $methods[] = [
                    'checkoutId' => $method->getId(),
                    'id' => $this->getIncrementalId($id, $i),
                    'name' => $method->getName(),
                    'group' => $method->getGroup(),
                    'icon' => $method->getIcon(),
                    'svg' => $method->getSvg()
                ];
            }
        }

        return $methods;
    }

    /**
     * Returns incremental id.
     *
     * @param string $id
     * @param int $i
     * @return string
     */
    public function getIncrementalId($id, int &$i): string
    {
        return $id . self::ID_INCREMENT_SEPARATOR . ($i++);
    }

    /**
     * Returns id without increment.
     *
     * @param string $id
     *
     * @return string
     */
    public function getIdWithoutIncrement(string $id): string
    {
        return explode(self::ID_INCREMENT_SEPARATOR, $id)[0];
    }

    /**
     * Returns card type.
     *
     * @param string $id
     *
     * @return ?string
     */
    public function getCardType(string $id): ?string
    {
        $idParts = explode(self::ID_INCREMENT_SEPARATOR, $id);

        if (count($idParts) == 3) {
            return $idParts[1];
        }

        return null;
    }
}

<?php
declare(strict_types=1);

namespace Nexi\Checkout\Plugin;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\GuestShippingInformationManagement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Nexi\Checkout\Gateway\Config\Config;

/**
 * Plugin to set email on quote during guest shipping information processing
 */
class GuestShippingInformationManagementPlugin
{

    /**
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartRepositoryInterface $cartRepository
     * @param Config $config
     */
    public function __construct(
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly Config $config
    ) {
    }

    /**
     * Set email on quote before saving shipping information
     *
     * @param GuestShippingInformationManagement $subject
     * @param string $cartId
     * @param ShippingInformationInterface $addressInformation
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function beforeSaveAddressInformation(
        GuestShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        if (!$this->config->isActive() || !$this->config->isEmbedded()) {
            return [$cartId, $addressInformation];
        }

//        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
//        $quoteId     = (int)$quoteIdMask->getQuoteId();
//
//        $shippingAddress = $addressInformation->getShippingAddress();
//        if ($shippingAddress && $shippingAddress->getEmail()) {
//            $quote = $this->cartRepository->getActive($quoteId);
//            $quote->setCustomerEmail($shippingAddress->getEmail());
//            $this->cartRepository->save($quote);
//        }
//
//        return [$cartId, $addressInformation];
    }
}

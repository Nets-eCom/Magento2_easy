<?php

namespace Nexi\Checkout\Gateway\Request\NexiCheckout;

use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Url;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order;
use Nexi\Checkout\Gateway\Config\Config;
use NexiCheckout\Model\Request\Payment;
use NexiCheckout\Model\Request\Payment\EmbeddedCheckout;
use NexiCheckout\Model\Request\Payment\HostedCheckout;
use NexiCheckout\Model\Request\Payment\IntegrationTypeEnum;
use NexiCheckout\Model\Webhook\EventNameEnum;

class SalesDocumentItemsBuilder
{

    /**
     * Build sales document items for the given sales object
     * 
     * @param CreditmemoInterface|InvoiceInterface $salesObject
     *
     * @return array
     */
    public function build(CreditmemoInterface|InvoiceInterface $salesObject): array
    {
        $items = [];
        foreach ($salesObject->getAllItems() as $item) {
            $items[] = new \NexiCheckout\Model\Request\Item(
                name            : $item->getName(),
                quantity        : (int)$item->getQty(),
                unit            : 'pcs',
                unitPrice       : (int)($item->getPrice() * 100),
                grossTotalAmount: (int)($item->getRowTotalInclTax() * 100),
                netTotalAmount  : (int)($item->getRowTotal() * 100),
                reference       : $item->getSku(),
                taxRate         : (int)($item->getTaxPercent() * 100),
                taxAmount       : (int)($item->getTaxAmount() * 100),
            );
        }

        if ($salesObject->getShippingInclTax()) {
            $items[] = new \NexiCheckout\Model\Request\Item(
                name            : $salesObject->getOrder()->getShippingDescription(),
                quantity        : 1,
                unit            : 'pcs',
                unitPrice       : (int)($salesObject->getShippingAmount() * 100),
                grossTotalAmount: (int)($salesObject->getShippingInclTax() * 100),
                netTotalAmount  : (int)($salesObject->getShippingAmount() * 100),
                reference       : $salesObject->getOrder()->getShippingMethod(),
                taxRate         : (int)($salesObject->getTaxAmount() / $salesObject->getGrandTotal() * 100),
                taxAmount       : (int)($salesObject->getShippingTaxAmount() * 100),
            );
        }

        return $items;
    }
}

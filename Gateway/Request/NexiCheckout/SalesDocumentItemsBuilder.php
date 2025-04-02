<?php

namespace Nexi\Checkout\Gateway\Request\NexiCheckout;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use NexiCheckout\Model\Request\Item;

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
            $items[] = new Item(
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
            $items[] = new Item(
                name            : $salesObject->getOrder()->getShippingDescription(),
                quantity        : 1,
                unit            : 'pcs',
                unitPrice       : (int)($salesObject->getShippingAmount() * 100),
                grossTotalAmount: (int)($salesObject->getShippingInclTax() * 100),
                netTotalAmount  : (int)($salesObject->getShippingAmount() * 100),
                reference       : $salesObject->getOrder()->getShippingMethod(),
                taxRate         : $salesObject->getGrandTotal() ? (int)($salesObject->getTaxAmount() / $salesObject->getGrandTotal() * 100) : 0,
                taxAmount       : (int)($salesObject->getShippingTaxAmount() * 100),
            );
        }

        return $items;
    }
}

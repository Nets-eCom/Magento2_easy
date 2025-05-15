<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway\Request\NexiCheckout;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Nexi\Checkout\Gateway\AmountConverter;
use Nexi\Checkout\Gateway\StringSanitizer;
use NexiCheckout\Model\Request\Item;

class SalesDocumentItemsBuilder
{
    public const SHIPPING_COST_REFERENCE = 'shipping_cost_ref';

    /**
     * @param AmountConverter $amountConverter
     * @param StringSanitizer $stringSanitizer
     */
    public function __construct(
        private readonly AmountConverter $amountConverter,
        private readonly StringSanitizer $stringSanitizer,
    ) {
    }

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
                name            : $this->stringSanitizer->sanitize($item->getName()),
                quantity        : (float)$item->getQty(),
                unit            : 'pcs',
                unitPrice       : $this->amountConverter->convertToNexiAmount($item->getPrice()),
                grossTotalAmount: $this->amountConverter->convertToNexiAmount($item->getRowTotalInclTax()),
                netTotalAmount  : $this->amountConverter->convertToNexiAmount($item->getRowTotal()),
                reference       : $this->stringSanitizer->sanitize($item->getSku()),
                taxRate         : $this->amountConverter->convertToNexiAmount($item->getTaxPercent()),
                taxAmount       : $this->amountConverter->convertToNexiAmount($item->getTaxAmount()),
            );
        }

        if ($salesObject->getShippingInclTax()) {
            $items[] = new Item(
                name            : $this->stringSanitizer->sanitize($salesObject->getOrder()->getShippingDescription()),
                quantity        : 1,
                unit            : 'pcs',
                unitPrice       : $this->amountConverter->convertToNexiAmount($salesObject->getShippingAmount()),
                grossTotalAmount: $this->amountConverter->convertToNexiAmount($salesObject->getShippingInclTax()),
                netTotalAmount  : $this->amountConverter->convertToNexiAmount($salesObject->getShippingAmount()),
                reference       : self::SHIPPING_COST_REFERENCE,
                taxRate         : $salesObject->getGrandTotal() ?
                    $this->amountConverter->convertToNexiAmount(
                        $salesObject->getTaxAmount() / $salesObject->getGrandTotal()
                    ) : 0,
                taxAmount       : $this->amountConverter->convertToNexiAmount($salesObject->getShippingTaxAmount()),
            );
        }

        return $items;
    }
}

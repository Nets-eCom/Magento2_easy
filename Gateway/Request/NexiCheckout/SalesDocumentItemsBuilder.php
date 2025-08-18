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
     * SalesDocumentItemsBuilder constructor.
     *
     * @param AmountConverter $amountConverter
     * @param StringSanitizer $stringSanitizer
     * @param GlobalRequestBuilder $globalRequestBuilder
     */
    public function __construct(
        private readonly AmountConverter $amountConverter,
        private readonly StringSanitizer $stringSanitizer,
        private readonly GlobalRequestBuilder $globalRequestBuilder,
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
        $items = $this->globalRequestBuilder->getProductsData($salesObject);

        if ($salesObject->getShippingInclTax()) {
            $items[] = new Item(
                name            : $this->stringSanitizer->sanitize($salesObject->getOrder()->getShippingDescription()),
                quantity        : 1,
                unit            : 'pcs',
                unitPrice       : $this->amountConverter->convertToNexiAmount($salesObject->getBaseShippingAmount()),
                grossTotalAmount: $this->amountConverter->convertToNexiAmount($salesObject->getBaseShippingInclTax()),
                netTotalAmount  : $this->amountConverter->convertToNexiAmount($salesObject->getBaseShippingAmount()),
                reference       : self::SHIPPING_COST_REFERENCE,
                taxRate         : $salesObject->getGrandTotal() ?
                    $this->amountConverter->convertToNexiAmount(
                        $this->calculateShippingTaxRate($salesObject)
                    ) : 0,
                taxAmount       : $this->amountConverter->convertToNexiAmount($salesObject->getBaseShippingTaxAmount()),
            );
        }

        return $items;
    }

    /**
     * Calculate the tax rate for a given item.
     *
     * @param mixed $item
     *
     * @return mixed
     */
    private function calculateTaxRate(mixed $item): mixed
    {
        return $item->getTaxAmount() / $item->getRowTotal() * 100;
    }

    /**
     * Calculate the shipping tax rate for a given sales object.
     *
     * @param InvoiceInterface|CreditmemoInterface $salesObject
     *
     * @return float|int
     */
    private function calculateShippingTaxRate(InvoiceInterface|CreditmemoInterface $salesObject): int|float
    {
        if ($salesObject->getShippingAmount() == 0) {
            return 0;
        }

        return $salesObject->getShippingTaxAmount() / $salesObject->getShippingAmount() * 100;
    }
}

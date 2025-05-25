<?php
declare(strict_types=1);

namespace Nexi\Checkout\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;

class PreventDifferentScheduledCart
{
    public const SCHEDULE_CODE = 'recurring_payment_schedule';

    /**
     * BeforeAddProduct plugin.
     *
     * @param Quote $subject
     * @param mixed $product
     * @param null|float|DataObject $request
     * @param null|string $processMode
     *
     * @return array
     * @throws LocalizedException
     */
    public function beforeAddProduct(
        Quote   $subject,
        Product $product,
                $request = null,
                $processMode = AbstractType::PROCESS_MODE_FULL
    ) {
        $cartItems       = $subject->getItems() ?: [];
        $addItemSchedule = $product->getCustomAttribute(self::SCHEDULE_CODE);
        if (!$addItemSchedule) {
            return [$product, $request, $processMode];
        }
        foreach ($cartItems as $item) {
            $cartItemSchedule = $item->getProduct()->getCustomAttribute(self::SCHEDULE_CODE);
            if ($cartItemSchedule && $cartItemSchedule->getValue() != $addItemSchedule->getValue()) {
                throw new LocalizedException(__("Can't add product with different payment schedule"));
            }
        }

        return [$product, $request, $processMode];
    }
}

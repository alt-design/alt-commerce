<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use AltDesign\AltCommerce\Commerce\Basket\DiscountItem;
use AltDesign\AltCommerce\Enum\DiscountType;
use Ramsey\Uuid\Uuid;

class ApplyManualDiscountAction
{
    public function __construct(
        protected BasketContext $context
    ) {

    }

    public function handle(int $amount, string $description = 'Manual discount'): void
    {
        $discountItem = new DiscountItem(
            id: Uuid::uuid4()->toString(),
            name: $description,
            amount: $amount,
            type: DiscountType::MANUAL
        );

        $basket = $this->context->current();

        $basket->discountItems[] = $discountItem;

    }
}
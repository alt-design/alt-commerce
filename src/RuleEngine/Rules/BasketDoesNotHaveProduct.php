<?php

namespace AltDesign\AltCommerce\RuleEngine\Rules;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use Carbon\Carbon;

class BasketDoesNotHaveProduct extends BaseRule
{
    /**
     * @param array<string> $productIds
     */
    public function __construct(
        protected array $productIds
    ) {

    }

    protected function handle(): void
    {
        /**
         * @var Basket $basket
         */
        $basket = $this->resolve('basket');
        foreach ($basket->lineItems as $lineItem) {
            if (in_array($lineItem->productId, $this->productIds)) {
                $this->fail('Basket contains matching product');
                break;
            }
        }
    }
}
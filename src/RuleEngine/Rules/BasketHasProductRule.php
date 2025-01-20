<?php

namespace AltDesign\AltCommerce\RuleEngine\Rules;

use AltDesign\AltCommerce\Commerce\Basket\Basket;

class BasketHasProductRule extends BaseRule
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
        $found = false;
        foreach ($basket->lineItems as $lineItem) {
            if (in_array($lineItem->productId, $this->productIds)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->fail('Basket does not contain matching products');
        }
    }
}
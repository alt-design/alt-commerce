<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\CouponDiscountItem;
use AltDesign\AltCommerce\Commerce\Basket\CouponItem;
use AltDesign\AltCommerce\Commerce\Basket\DeliveryItem;
use AltDesign\AltCommerce\Commerce\Basket\FeeItem;
use AltDesign\AltCommerce\Commerce\Basket\TaxItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\DiscountItem;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Enum\DiscountType;
use AltDesign\AltCommerce\Support\WeightedAverageCalculator;
use AltDesign\AltCommerce\Traits\InteractWithBasket;


class RecalculateBasketAction
{
    use InteractWithBasket;

    public function __construct(
        protected BasketRepository $basketRepository,
        protected ProductRepository $productRepository,
    )
    {

    }

    public function handle(): void
    {
        $basket = $this->basketRepository->get();

        //$this->refreshProducts($basket);

        $this->calculateLineItemTotals($basket);
        $this->calculateDiscountItems($basket);

        $this->calculateTaxItems($basket);
        $this->calculateTotals($basket);

        $this->basketRepository->save($basket);
    }

    protected function refreshProducts(Basket $basket): void
    {
        foreach ($basket->lineItems as $lineItem) {
            $product = $this->productRepository->find($lineItem->productId);
            // todo - ensure product is still available and in stock
        }
    }

    protected function calculateDiscountItems(Basket $basket): void
    {
        $basket->discountItems = [];
        foreach ($basket->coupons as $couponItem) {
            $discountAmount = $couponItem->coupon->discountType() === DiscountType::FIXED ?
                $couponItem->coupon->discountAmount() :
                $basket->subTotal * $couponItem->coupon->discountAmount() / 100;

            $basket->discountItems[] = new CouponDiscountItem(
                name: $couponItem->coupon->name(),
                amount: $discountAmount,
                coupon: $couponItem->coupon,
            );

        }
    }

    protected function calculateLineItemTotals(Basket $basket): void
    {
        $basket->subTotal = 0;
        foreach ($basket->lineItems as $lineItem) {
            $lineItem->amount = $lineItem->subTotal * $lineItem->quantity;
            $lineItem->discountAmount = 0; // todo reserved for line specific discount amount
            $basket->subTotal += $lineItem->amount;
        }
    }

    private function calculateTaxItems(Basket $basket): void
    {
        $basket->taxItems = [];
        foreach ($basket->lineItems as $lineItem) {

            $taxRules = [];
            foreach ($lineItem->taxRules as $taxRule) {
                if (!in_array($basket->countryCode, $taxRule->countries)) {
                    continue;
                }
                $taxRules[] = $taxRule;
            }

            if (!$lineItem->taxable || empty($taxRules)) {
                continue;
            }

            foreach ($taxRules as $taxRule) {
                $basket->taxItems[] = new TaxItem(
                    name: $taxRule->name,
                    amount: ($lineItem->amount + $lineItem->discountAmount) * $taxRule->rate / 100,
                    rate: $taxRule->rate
                );

            }
        }

        // Calculate tax for discounts
        if (!empty($basket->discountItems) && !empty($basket->taxItems)) {

            $calculator = new WeightedAverageCalculator();
            foreach ($basket->taxItems as $taxItem) {
                if ($taxItem->rate === 0) {
                    continue;
                }

                $calculator->addValue($taxItem->rate, $taxItem->amount);
            }

            $taxRateAverage = $calculator->calculate();
            $discountAmount = array_reduce($basket->discountItems, fn($sum, DiscountItem $item) => $sum + $item->amount(), 0);

            // 99.99% of baskets will only have 1 tax rate, so apply the same name to the discount
            if ($taxRateAverage > 0) {
                $basket->taxItems[] = new TaxItem(
                    name: $basket->taxItems[0]->name,
                    amount: (int)($discountAmount * $taxRateAverage / 100 * -1),
                    rate: (int)$taxRateAverage
                );
            }
        }
    }

    protected function calculateTotals(Basket $basket): void
    {
        $basket->taxTotal = array_reduce($basket->taxItems, fn($sum, TaxItem $item) => $sum + $item->amount, 0);
        $basket->deliveryTotal = array_reduce($basket->deliveryItems, fn($sum, DeliveryItem $item) => $sum + $item->amount, 0);
        $basket->feeTotal = array_reduce($basket->feeItems, fn($sum, FeeItem $item) => $sum + $item->amount, 0);
        $basket->discountTotal = array_reduce($basket->discountItems, fn($sum, DiscountItem $item) => $sum + $item->amount()*-1, 0);
        $basket->total = max(
            $basket->subTotal +
            $basket->taxTotal +
            $basket->deliveryTotal +
            $basket->feeTotal +
            $basket->discountTotal, 0
        );
    }


}
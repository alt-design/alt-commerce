<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\CouponDiscountItem;
use AltDesign\AltCommerce\Commerce\Basket\DeliveryItem;
use AltDesign\AltCommerce\Commerce\Basket\FeeItem;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
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

        $this->refreshProducts($basket);

        $this->calculateLineItemTotals($basket);
        $this->calculateDiscountItems($basket);

        $this->calculateTaxItems($basket);
        $this->calculateTotals($basket);

        $this->calculateLineItemDiscounts($basket);
        $this->calculateLineItemTax($basket);

        $this->basketRepository->save($basket);
    }

    protected function refreshProducts(Basket $basket): void
    {
        // todo - ensure product is still available and in stock
    }

    protected function calculateDiscountItems(Basket $basket): void
    {
        // remove coupon discounts items as they need to be recalculated
        foreach ($basket->discountItems as $key => $item) {
            if ($item instanceof CouponDiscountItem) {
                unset($basket->discountItems[$key]);
            }
        }

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

    protected function calculateLineItemTax(Basket $basket): void
    {
        foreach ($basket->lineItems as $lineItem) {
            if ($taxItem = $this->getTaxItemForLineItem($basket, $lineItem)) {
                $lineItem->taxTotal = $taxItem->amount;
                $lineItem->taxRate = $taxItem->rate;
                $lineItem->taxName = $taxItem->name;
            }
        }
    }

    protected function calculateLineItemDiscounts(Basket $basket): void
    {

        foreach ($basket->discountItems as $discountItem) {
            if ( !($discountItem instanceof CouponDiscountItem)) {
                continue;
            }

            $runningTotal = 0;
            $maxDiscountAmount = 0;
            $maxDiscountKey = null;
            foreach ($basket->lineItems as $key => $lineItem) {
                $weight = $lineItem->subTotal / $basket->subTotal;

                $discountTotal = intval($discountItem->amount() * $weight);
                $lineItem->discountTotal += $discountTotal;
                $runningTotal += $discountTotal;

                if ($discountTotal > $maxDiscountAmount) {
                    $maxDiscountKey = $key;
                    $maxDiscountAmount = $discountTotal;
                }
            }

            // adjust for rounding errors
            $diff = $runningTotal - $discountItem->amount();
            if ($diff !== 0) {
                $basket->lineItems[$maxDiscountKey]->discountTotal -= $diff;
            }
        }
    }

    protected function calculateLineItemTotals(Basket $basket): void
    {
        $basket->subTotal = 0;
        foreach ($basket->lineItems as $lineItem) {
            $lineItem->subTotal = $lineItem->amount * $lineItem->quantity;
            $lineItem->discountTotal = 0;
            $lineItem->taxTotal = 0;
            $basket->subTotal += $lineItem->subTotal;
        }
    }

    protected function calculateTaxItems(Basket $basket): void
    {
        $basket->taxItems = [];
        foreach ($basket->lineItems as $lineItem) {
            if ($taxItem = $this->getTaxItemForLineItem($basket, $lineItem)) {
                $basket->taxItems[] = $taxItem;
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
        $basket->discountTotal = array_reduce($basket->discountItems, fn($sum, DiscountItem $item) => $sum + $item->amount(), 0);
        $basket->total = max(
            $basket->subTotal +
            $basket->taxTotal +
            $basket->deliveryTotal +
            $basket->feeTotal -
            $basket->discountTotal, 0
        );
    }

    protected function getTaxItemForLineItem(Basket $basket, LineItem $lineItem): ?TaxItem
    {
        $taxRules = [];
        foreach ($lineItem->taxRules as $taxRule) {
            if (!empty($taxRule->countryFilter) && !in_array($basket->countryCode, $taxRule->countryFilter)) {
                continue;
            }
            $taxRules[] = $taxRule;
        }

        if (!$lineItem->taxable || empty($taxRules)) {
            return null;
        }

        // For now only support first tax rule... Can't think of any scenario where we would need 2 tax rules.
        // We rely on the repository to order tax rules by priority.
        $taxRule = $taxRules[0];
        $taxTotal = ($lineItem->subTotal + $lineItem->discountTotal) * $taxRule->rate / 100;

        return new TaxItem(
            name: $taxRule->name,
            amount: $taxTotal,
            rate: $taxRule->rate
        );


    }


}
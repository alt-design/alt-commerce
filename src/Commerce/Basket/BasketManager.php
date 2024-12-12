<?php

namespace AltDesign\AltCommerce\Commerce\Basket;


use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\DiscountItem;
use AltDesign\AltCommerce\Traits\InteractWithBasket;

class BasketManager
{
    use InteractWithBasket {
        InteractWithBasket::find as traitFind;
    }
    
    protected Basket $basket;

    public function __construct(
        BasketRepository $basketRepository,
    )
    {
        $this->basket = $basketRepository->get();
    }

    public function find(string $productId): ?LineItem
    {
        return $this->traitFind($this->basket, $productId);
    }
    
    public function currency(): string
    {
        return $this->basket->currency;
    }

    public function countryCode(): string
    {
        return $this->basket->countryCode;
    }

    public function total(): int
    {
        return $this->basket->total;
    }

    public function subTotal(): int
    {
        return $this->basket->subTotal;
    }

    public function taxTotal(): int
    {
        return $this->basket->taxTotal;
    }

    public function deliveryTotal(): int
    {
        return $this->basket->deliveryTotal;
    }

    public function feeTotal(): int
    {
        return $this->basket->feeTotal;
    }

    public function discountTotal(): int
    {
        return $this->basket->discountTotal;
    }

    /**
     * @return LineItem[]
     */
    public function lineItems(): array
    {
        return $this->basket->lineItems;
    }

    /**
     * @param bool $grouped
     * @return TaxItem[]
     */
    public function taxItems(bool $grouped = true): array
    {
        if ($grouped) {
            $grouped = [];
            foreach ($this->basket->taxItems as $taxItem) {


                if (isset($grouped[$taxItem->name])) {
                    $grouped[$taxItem->name]->amount += $taxItem->amount;
                    continue;
                }

                $grouped[$taxItem->name] = new TaxItem(
                    name: $taxItem->name,
                    amount: $taxItem->amount,
                    rate: $taxItem->rate
                );
            }
            return $grouped;

        }
        return $this->basket->taxItems;
    }

    /**
     * @return DeliveryItem[]
     */
    public function deliveryItems(): array
    {
        return $this->basket->deliveryItems;
    }

    /**
     * @return FeeItem[]
     */
    public function feeItems(): array
    {
        return $this->basket->feeItems;
    }

    /**
     * @return CouponItem[]
     */
    public function coupons(): array
    {
        return $this->basket->coupons;
    }

    /**
     * @return DiscountItem[]
     */
    public function discountItems(): array
    {
        return $this->basket->discountItems;
    }



}
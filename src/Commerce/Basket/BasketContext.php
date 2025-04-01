<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Contracts\BasketDriver;
use AltDesign\AltCommerce\Contracts\Resolver;

/**
 * @method int addToBasket(string $productId, int $quantity = 1, int $price = null, array $options = [])
 */
class BasketContext
{
    public function __construct(protected Resolver $resolver, protected BasketDriver $driver, protected string $context)
    {

    }

    protected function resolveWithContext(string $abstract)
    {
        return $this->resolver->resolve($abstract, [
            'context' => $this
        ]);
    }

    public function __call($name, $arguments)
    {
        $action = 'AltDesign\\AltCommerce\\Actions\\'.$name.'Action';
        if (!class_exists($action)) {
            throw new \BadMethodCallException("Action {$name} does not exist");
        }

        return $this->resolveWithContext($action)->handle(...$arguments);

    }

    public function find(string $productId): LineItem|BillingItem|null
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

    public function tap(callable $callback): BasketContext
    {
        $callback($this);
        return $this;
    }

    /**
     * @return LineItem[]
     */
    public function lineItems(): array
    {
        return $this->basket->lineItems;
    }

    /**
     * @return BillingItem[]
     */
    public function billingItems(): array
    {
        return $this->basket->billingItems;
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

    public function isEmpty(): bool
    {
        return empty($this->lineItems()) && empty($this->billingItems());
    }


    public function save(Basket $basket): void
    {
       $this->driver->save($basket);
    }

    public function current(): Basket
    {
        return $this->driver->get();
    }
}
<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Contracts\BasketDriver;
use AltDesign\AltCommerce\Contracts\Resolver;
use AltDesign\AltCommerce\Traits\InteractWithBasket;

/**
 * @method int addToBasket(string $productId, int $quantity = 1, int $price = null, array $options = [])
 * @method void recalculateBasket()
 * @method LineItem[] lineItems()
 * @method BillingItem[] billingItems()
 */
class BasketContext
{
    use InteractWithBasket {
        InteractWithBasket::find as traitFind;
    }

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

        if (property_exists($this->current(), $name)) {
            return $this->current()->$name;
        }

        $action = 'AltDesign\\AltCommerce\\Actions\\'.ucfirst($name).'Action';
        if (!class_exists($action)) {
            throw new \BadMethodCallException("Action {$name} does not exist");
        }

        // Todo, need some form of Action context here, the ability to perform other actions after the primary one
        $action = $this->resolveWithContext($action);
        $result = $action->handle(...$arguments);

        $this->recalculateBasket();
        return $result;
    }

    public function find(string $productId): LineItem|BillingItem|null
    {
        return $this->traitFind($this->current(), $productId);
    }



    public function tap(callable $callback): BasketContext
    {
        $callback($this);
        return $this;
    }

    /**
     * @param bool $grouped
     * @return TaxItem[]
     */
    public function taxItems(bool $grouped = true): array
    {
        if ($grouped) {
            $grouped = [];
            foreach ($this->current()->taxItems as $taxItem) {


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
        return $this->current()->taxItems;
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

    public function clear(): void
    {
        $this->driver->delete();
    }
}
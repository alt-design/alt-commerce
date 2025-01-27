<?php

namespace AltDesign\AltCommerce\Support;

use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;
use Traversable;

/**
 * @implements \ArrayAccess<int, Money>
 * @implements \IteratorAggregate<int, Money>
 */
final class PriceCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{

    /**
     * @param Money[] $prices
     */
    public function __construct(protected array $prices = [])
    {

    }

    public function isCurrencySupported(string $currency): bool
    {
        return !!$this->find($currency);
    }

    public function getAmount(string $currency): int
    {
        if ($money = $this->find($currency)) {
            return $money->amount;
        }

        throw new CurrencyNotSupportedException($currency);
    }

    protected function find(string $currency): ?Money
    {
        foreach ($this->prices as $price) {
            if ($price->currency === strtoupper($currency)) {
                return $price;
            }
        }
        return null;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->prices);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->prices[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->prices[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->prices[] = $value;
        } else {
            $this->prices[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->prices[$offset]);
    }

    public function count(): int
    {
        return count($this->prices);
    }
}
<?php

namespace AltDesign\AltCommerce\Services;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Contracts\StockRepository;
use AltDesign\AltCommerce\Enum\StockPolicy;

/**
 * Availability rules for counted inventory. Framework-agnostic: it reads levels
 * through the StockRepository contract, so any adapter (Statamic, WordPress, …)
 * reuses these rules unchanged.
 */
class StockService
{
    public function __construct(
        protected StockRepository $stockRepository,
        protected ProductRepository $productRepository,
    ) {}

    /**
     * Raw on-hand level, or null when the product isn't tracked. Tracked and
     * backorder products always resolve to a number (defaulting to 0).
     */
    public function level(Product $product): ?int
    {
        if ($product->stockPolicy() === StockPolicy::UNTRACKED) {
            return null;
        }

        return $this->stockRepository->available($product->id()) ?? 0;
    }

    /**
     * How many units may be purchased right now. null means unlimited
     * (untracked, or backorder which is always buyable).
     */
    public function purchasableQuantity(Product $product): ?int
    {
        return match ($product->stockPolicy()) {
            StockPolicy::UNTRACKED, StockPolicy::BACKORDER => null,
            StockPolicy::TRACKED => max(0, $this->level($product) ?? 0),
        };
    }

    public function isPurchasable(Product $product, int $quantity = 1): bool
    {
        $limit = $this->purchasableQuantity($product);

        return $limit === null || $limit >= $quantity;
    }

    /**
     * Clamp a desired quantity down to what may be purchased.
     */
    public function clamp(Product $product, int $quantity): int
    {
        $limit = $this->purchasableQuantity($product);

        return $limit === null ? $quantity : min($quantity, $limit);
    }

    /**
     * Whether the pre-order / lead-time state should show: a backorder product
     * that has reached or dropped below zero.
     */
    public function isOnBackorder(Product $product): bool
    {
        return $product->stockPolicy() === StockPolicy::BACKORDER
            && ($this->level($product) ?? 0) <= 0;
    }

    /**
     * Reduce or drop basket lines that exceed available stock, returning the
     * changes so the caller can message the customer. The basket is mutated in
     * place; persisting it is the caller's responsibility.
     *
     * @return array<int, array{product_id: string, name: string, from: int, to: int, removed: bool}>
     */
    public function reconcileBasket(Basket $basket): array
    {
        $changes = [];
        $kept = [];

        foreach ($basket->lineItems as $lineItem) {
            $product = $this->productRepository->find($lineItem->productId);

            if (! $product) {
                $kept[] = $lineItem;

                continue;
            }

            $allowed = $this->clamp($product, $lineItem->quantity);

            if ($allowed >= $lineItem->quantity) {
                $kept[] = $lineItem;

                continue;
            }

            $changes[] = [
                'product_id' => $lineItem->productId,
                'name' => $lineItem->productName,
                'from' => $lineItem->quantity,
                'to' => $allowed,
                'removed' => $allowed <= 0,
            ];

            if ($allowed > 0) {
                $lineItem->quantity = $allowed;
                $kept[] = $lineItem;
            }
        }

        $basket->lineItems = $kept;

        return $changes;
    }
}

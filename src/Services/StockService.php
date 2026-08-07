<?php

namespace AltDesign\AltCommerce\Services;

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

    /**
     * purchasableQuantity() resolved from a product id. null when the product
     * can't be found or is unlimited.
     */
    public function purchasableQuantityForId(string $productId): ?int
    {
        $product = $this->productRepository->find($productId);

        return $product ? $this->purchasableQuantity($product) : null;
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
}

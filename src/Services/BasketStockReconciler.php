<?php

namespace AltDesign\AltCommerce\Services;

use AltDesign\AltCommerce\Commerce\Basket\BasketManager;
use AltDesign\AltCommerce\Contracts\ProductRepository;

/**
 * Self-heals a basket against live stock: reduces over-quantity lines to the
 * available level and drops out-of-stock ones, persisting through the basket
 * context. Returns the structured changes so the caller can message the shopper
 * however it likes. Framework-agnostic: it only talks to core contracts.
 */
class BasketStockReconciler
{
    public function __construct(
        protected BasketManager $basket,
        protected ProductRepository $products,
        protected StockService $stock,
    ) {}

    /**
     * @return array<int, array{product_id: string, name: string, from: int, to: int, removed: bool}>
     */
    public function reconcile(string $context = 'default'): array
    {
        $changes = [];
        $lines = $this->basket->context($context)->current()->lineItems;

        foreach ($lines as $item) {
            $product = $this->products->find($item->productId);

            if (! $product) {
                continue;
            }

            $allowed = $this->stock->clamp($product, $item->quantity);

            if ($allowed >= $item->quantity) {
                continue;
            }

            if ($allowed <= 0) {
                $this->basket->context($context)->removeFromBasket($item->id);
            } else {
                $this->basket->context($context)->updateBasketQuantity($item->productId, $allowed);
            }

            $changes[] = [
                'product_id' => $item->productId,
                'name' => $item->productName,
                'from' => $item->quantity,
                'to' => max(0, $allowed),
                'removed' => $allowed <= 0,
            ];
        }

        return $changes;
    }
}

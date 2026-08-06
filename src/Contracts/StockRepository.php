<?php

namespace AltDesign\AltCommerce\Contracts;

interface StockRepository
{
    /**
     * Current on-hand level for a product, or null when the product has no
     * stock record (i.e. it is not tracked).
     */
    public function available(string $productId): ?int;

    /**
     * Record a stock movement and update the level in a single transaction.
     * A positive quantity restocks, a negative quantity reduces. The level is
     * permitted to go negative (backorders), the caller decides the policy.
     */
    public function adjust(string $productId, int $quantity, ?string $reason = null, ?string $reference = null, ?string $note = null): void;

    /**
     * Whether any movement has already been recorded against a reference.
     * Used to keep order-driven reductions idempotent.
     */
    public function hasMovementsForReference(string $reference): bool;
}

<?php

namespace AltDesign\AltCommerce\Contracts;


interface Coupon
{
    public function name(): string;

    public function code(): string;

    public function currency(): string;

    public function startDate(): \DateTimeImmutable|null;

    public function endDate(): \DateTimeImmutable|null;

    public function discountAmount(): int;

    public function isPercentage(): bool;

    /**
     * Minimum basket spend in minor units. Zero means no minimum.
     */
    public function minimumSpend(): int;

}
<?php

namespace AltDesign\AltCommerce\Support;

use AltDesign\AltCommerce\Enum\DurationUnit;

class Duration
{
    public function __construct(
        public int $amount,
        public DurationUnit $unit,
    )
    {
    }

    public function days(): int
    {
        return match($this->unit) {
            DurationUnit::DAY => $this->amount,
            DurationUnit::WEEK => $this->amount * 7,
            DurationUnit::MONTH => $this->amount * 30,
            DurationUnit::YEAR => $this->amount * 365,
        };
    }

    public function __toString()
    {
        return $this->amount.':'.$this->unit->value;
    }

    public static function convert(int $amount, Duration $from, Duration $to): int
    {
        if ((string)$from === (string)$to) {
            return $amount;
        }

        $pricePerDay = $amount / $from->days();

        return (int)($pricePerDay * $to->days());
    }
}
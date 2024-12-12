<?php

namespace AltDesign\AltCommerce\RuleEngine\Rules;

use Carbon\Carbon;
use DateTimeImmutable;
use DateTimeInterface;

class DateConstraintRule extends BaseRule
{
    public function __construct(
        protected DateTimeImmutable|null $min = null,
        protected DateTimeImmutable|null $max = null,
    ) {

    }

    protected function handle(): void
    {
        if (($this->min instanceof DateTimeInterface) && Carbon::now()->isBefore($this->min)) {
            $this->fail('Minimum date is in the future');
        }

        if (($this->max instanceof DateTimeInterface) && Carbon::now()->isAfter($this->max)) {
            $this->fail('Maximum date is in the past');
        }
    }

}


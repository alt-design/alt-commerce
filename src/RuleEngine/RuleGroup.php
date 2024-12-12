<?php

namespace AltDesign\AltCommerce\RuleEngine;

use AltDesign\AltCommerce\Contracts\Rule;
use AltDesign\AltCommerce\Enum\RuleMatchingType;

class RuleGroup
{
    /**
     * @param Rule[] $rules
     * @param RuleMatchingType $matchingType
     */
    public function __construct(
        public array $rules,
        public RuleMatchingType $matchingType = RuleMatchingType::ALL
    )
    {

    }
}
<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\RuleEngine\EvaluationResult;

interface Rule
{
    /**
     * @param array<string, mixed> $context
     * @return EvaluationResult
     */
    public function evaluate(array $context): EvaluationResult;
}
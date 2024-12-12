<?php

namespace AltDesign\AltCommerce\RuleEngine;

class EvaluationResult
{
    /**
     * @param bool $result
     * @param array<mixed> $logs
     */
    public function __construct(
        public bool $result,
        public array $logs = []
    ) {

    }
}
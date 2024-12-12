<?php

namespace AltDesign\AltCommerce\RuleEngine;


use AltDesign\AltCommerce\Enum\RuleMatchingType;


class RuleManager
{
    /**
     * @param RuleGroup $ruleGroup
     * @param array<string,mixed> $context
     * @return EvaluationResult
     */
    public function evaluate(RuleGroup $ruleGroup, array $context): EvaluationResult
    {
        $results = [];

        $passed = $this->loop($ruleGroup, $context, $results);

        return new EvaluationResult(result: $passed);
    }

    /**
     * @param RuleGroup $ruleGroup
     * @param array<string,mixed> $context
     * @param EvaluationResult[] $results
     * @return bool
     */
    protected function loop(RuleGroup $ruleGroup, array $context, array &$results): bool
    {
        $passed = [];
        foreach ($ruleGroup->rules as $rule) {
            if ($rule instanceof RuleGroup) {
                if ($this->loop($rule, $context, $results)) {
                    $passed[] = $rule;
                }
                continue;
            }

            $result = $rule->evaluate($context);
            if ($result->result) {
                $passed[] = $rule;
            }
            $results[] = $result;
        }

        if ($ruleGroup->matchingType === RuleMatchingType::ALL) {
            return count($passed) === count($ruleGroup->rules);
        }

        return count($passed) > 0;

    }
}
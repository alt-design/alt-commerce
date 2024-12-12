<?php

namespace AltDesign\AltCommerce\Tests\Unit\RuleEngine\Rules;

use AltDesign\AltCommerce\RuleEngine\Rules\DateConstraintRule;
use Carbon\Carbon;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DateConstraintRuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2024-01-15 14:30:00');
    }

    public function test_passes_with_min_date(): void
    {
        $rule = new DateConstraintRule(
            min: DateTimeImmutable::createFromFormat('Y-m-d', '2022-01-01') ?: null,
        );

        $this->assertTrue($rule->evaluate()->result);
    }

    public function test_fails_with_min_date(): void
    {
        $rule = new DateConstraintRule(
            min: DateTimeImmutable::createFromFormat('Y-m-d', '2024-10-01') ?: null,
        );

        $this->assertFalse($rule->evaluate()->result);
    }

    public function test_passes_with_max_date(): void
    {
        $rule = new DateConstraintRule(
            max: DateTimeImmutable::createFromFormat('Y-m-d', '2025-01-01') ?: null,
        );

        $this->assertTrue($rule->evaluate()->result);
    }

    public function test_fails_with_max_date(): void
    {
        $rule = new DateConstraintRule(
            max: DateTimeImmutable::createFromFormat('Y-m-d', '2023-01-01') ?: null,
        );

        $this->assertFalse($rule->evaluate()->result);
    }

    public function test_passes_with_min_and_max_date(): void
    {
        $rule = new DateConstraintRule(
            min: DateTimeImmutable::createFromFormat('Y-m-d', '2022-01-01') ?: null,
            max: DateTimeImmutable::createFromFormat('Y-m-d', '2025-01-01') ?: null,
        );

        $this->assertTrue($rule->evaluate()->result);
    }

    public function test_fails_with_min_and_max_date(): void
    {
        $rule = new DateConstraintRule(
            min: DateTimeImmutable::createFromFormat('Y-m-d', '2021-01-01') ?: null,
            max: DateTimeImmutable::createFromFormat('Y-m-d', '2023-01-01') ?: null,
        );

        $this->assertFalse($rule->evaluate()->result);
    }


}
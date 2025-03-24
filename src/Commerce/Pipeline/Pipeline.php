<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline;

abstract class Pipeline
{
    /**
     * @var array<object>
     */
    protected static array $jobs = [];

    public static function register(object ...$job): void
    {
        array_push(self::$jobs, ...$job);
    }

    /**
     * @param mixed ...$args
     * @return void
     */
    protected function run(...$args): void
    {
        foreach (self::$jobs as $job) {
            // @phpstan-ignore-next-line
            $job->handle(...$args);
        }
    }
}
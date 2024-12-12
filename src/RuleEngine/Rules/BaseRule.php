<?php

namespace AltDesign\AltCommerce\RuleEngine\Rules;

use AltDesign\AltCommerce\Contracts\Rule;
use AltDesign\AltCommerce\RuleEngine\EvaluationResult;
use InvalidArgumentException;

abstract class BaseRule implements Rule
{

    /**
     * @var mixed[]
     */
    protected array $logs = [];

    private bool $passed = true;

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    /**
     * @param array<string, mixed> $context
     * @return EvaluationResult
     */
    public function evaluate(array $context = []): EvaluationResult
    {
        $this->log('Processing '.static::class);

        $this->context = $context;

        $this->handle();

        if ($this->passed) {
            $this->log('Passed');
        }

        return new EvaluationResult(
            result: $this->passed,
            logs: $this->logs,
        );
    }

    protected function fail(string|null $msg = null): void
    {
        $this->log($msg ? 'Failed: '.$msg : 'Failed');
        $this->passed = false;
    }

    protected function resolve(string $key): mixed
    {
        if (empty($this->context[$key])) {
            throw new InvalidArgumentException('No context exists for key '.$key);
        }
        return $this->context[$key];
    }

    protected function setContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    private function log(string $msg): void
    {
        $this->logs[] = [
            'msg' => $msg,
            'context' => $this->context,
        ];
    }

    abstract protected function handle(): void;

}
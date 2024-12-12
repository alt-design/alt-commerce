<?php

namespace AltDesign\AltCommerce\Support;

use Exception;

class WeightedAverageCalculator
{
    /**
     * @var float[]
     */
    protected array $values = [];

    /**
     * @var float[]
     */
    protected array $weights = [];

    public function addValue(float $value, float $weight): void
    {
        $this->values[] = $value;
        $this->weights[] = $weight;
    }

    public function calculate(): float
    {
        $totalWeight = array_sum($this->weights);
        if ($totalWeight == 0) {
            throw new Exception("weight cannot be zeo");
        }

        $weightedSum = 0;
        foreach ($this->values as $index => $value) {
            $weightedSum += $value * $this->weights[$index];
        }

        return $weightedSum / $totalWeight;
    }
}
<?php

namespace App\Services\Budget;

class DerivedCalculationService
{
    /**
     * Calculate row total: rateQuantity × rateMultiplier × rateDuration
     *
     * @param  float  $rateQuantity
     * @param  float  $rateMultiplier
     * @param  float  $rateDuration
     * @return float
     */
    public function calculateRowTotal(
        float $rateQuantity,
        float $rateMultiplier,
        float $rateDuration
    ): float {
        return $rateQuantity * $rateMultiplier * $rateDuration;
    }

    /**
     * Calculate phase total as sum of row totals.
     *
     * Accepts iterable of row objects or arrays with rate_quantity, rate_multiplier, rate_duration.
     *
     * @param  iterable<int, array{rate_quantity?: float|int, rate_multiplier?: float|int, rate_duration?: float|int}|object>  $rows
     * @return float
     */
    public function calculatePhaseTotal(iterable $rows): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $q = (float) ($this->extractRowValue($row, 'rate_quantity') ?? 0);
            $m = (float) ($this->extractRowValue($row, 'rate_multiplier') ?? 0);
            $d = (float) ($this->extractRowValue($row, 'rate_duration') ?? 0);
            $total += $this->calculateRowTotal($q, $m, $d);
        }

        return $total;
    }

    /**
     * Calculate project total from phase totals or phase row collections.
     *
     * If each item is numeric (phase total), sum directly.
     * If each item is iterable (phase rows), call calculatePhaseTotal() and sum.
     *
     * @param  iterable<int, float|int|iterable>  $phases
     * @return float
     */
    public function calculateProjectTotal(iterable $phases): float
    {
        $total = 0.0;
        foreach ($phases as $phase) {
            if (is_numeric($phase)) {
                $total += (float) $phase;
            } else {
                $total += $this->calculatePhaseTotal($phase);
            }
        }

        return $total;
    }

    /**
     * Calculate remaining balance: total budget minus total expenses.
     *
     * @param  float  $totalBudget
     * @param  float  $totalExpenses
     * @return float
     */
    public function calculateRemainingBalance(float $totalBudget, float $totalExpenses): float
    {
        return $totalBudget - $totalExpenses;
    }

    /**
     * Calculate utilization percentage: (expenses / openingBalance) * 100.
     * Returns 0 when openingBalance is 0 or less.
     *
     * @param  float  $expenses
     * @param  float  $openingBalance
     * @return float
     */
    public function calculateUtilization(float $expenses, float $openingBalance): float
    {
        if ($openingBalance <= 0) {
            return 0.0;
        }
        return ($expenses / $openingBalance) * 100;
    }

    /**
     * Extract value from row (object or array).
     */
    private function extractRowValue(object|array $row, string $key): mixed
    {
        if (is_array($row)) {
            return $row[$key] ?? null;
        }

        return $row->{$key} ?? null;
    }
}

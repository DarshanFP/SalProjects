<?php

namespace Tests\Unit\Budget;

use App\Services\Budget\DerivedCalculationService;
use Tests\TestCase;

/**
 * Phase 2.4 — Budget Formula Parity Guard
 *
 * Ensures frontend (JS) and backend (DerivedCalculationService) budget formulas
 * produce identical results. Simulates JS logic in PHP since PHP cannot execute browser JS.
 */
class BudgetFormulaParityTest extends TestCase
{
    private DerivedCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(DerivedCalculationService::class);
    }

    public function test_deterministic_dataset_js_and_backend_produce_identical_project_total(): void
    {
        $rows = [
            ['rate_quantity' => 2, 'rate_multiplier' => 3, 'rate_duration' => 4],
            ['rate_quantity' => 1.5, 'rate_multiplier' => 2, 'rate_duration' => 3],
            ['rate_quantity' => 10, 'rate_multiplier' => 0.5, 'rate_duration' => 8],
        ];

        $jsTotal = $this->computeJsEquivalent($rows);
        $backendTotal = $this->service->calculateProjectTotal([$rows]);

        $this->assertEqualsWithDelta(
            $jsTotal,
            $backendTotal,
            0.000001,
            'JS and Backend budget formulas must remain identical.'
        );
    }

    public function test_large_numbers_js_and_backend_produce_identical_results(): void
    {
        $rows = [
            ['rate_quantity' => 1000000, 'rate_multiplier' => 5000, 'rate_duration' => 12],
            ['rate_quantity' => 999999.99, 'rate_multiplier' => 1, 'rate_duration' => 1],
        ];

        $jsTotal = $this->computeJsEquivalent($rows);
        $backendTotal = $this->service->calculateProjectTotal([$rows]);

        $this->assertEqualsWithDelta(
            $jsTotal,
            $backendTotal,
            0.000001,
            'JS and Backend must produce identical results for large numbers.'
        );
    }

    public function test_decimals_js_and_backend_produce_identical_results(): void
    {
        $rows = [
            ['rate_quantity' => 0.1, 'rate_multiplier' => 0.2, 'rate_duration' => 0.3],
            ['rate_quantity' => 1.111, 'rate_multiplier' => 2.222, 'rate_duration' => 3.333],
        ];

        $jsTotal = $this->computeJsEquivalent($rows);
        $backendTotal = $this->service->calculateProjectTotal([$rows]);

        $this->assertEqualsWithDelta(
            $jsTotal,
            $backendTotal,
            0.000001,
            'JS and Backend must produce identical results for decimals.'
        );
    }

    public function test_zero_handling_js_and_backend_produce_identical_results(): void
    {
        $rows = [
            ['rate_quantity' => 0, 'rate_multiplier' => 100, 'rate_duration' => 100],
            ['rate_quantity' => 100, 'rate_multiplier' => 0, 'rate_duration' => 100],
            ['rate_quantity' => 100, 'rate_multiplier' => 100, 'rate_duration' => 0],
        ];

        $jsTotal = $this->computeJsEquivalent($rows);
        $backendTotal = $this->service->calculateProjectTotal([$rows]);

        $this->assertEqualsWithDelta(
            $jsTotal,
            $backendTotal,
            0.000001,
            'JS and Backend must produce identical results for zero handling.'
        );
        $this->assertSame(0.0, $jsTotal);
    }

    /**
     * Simulates JS budget-calculations.js logic:
     * rowTotal = quantity × multiplier × duration
     * phaseTotal = sum(rowTotals)
     * projectTotal = sum(phaseTotals)
     */
    private function computeJsEquivalent(array $rows): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $q = (float) ($row['rate_quantity'] ?? 0);
            $m = (float) ($row['rate_multiplier'] ?? 0);
            $d = (float) ($row['rate_duration'] ?? 0);
            $total += $q * $m * $d;
        }

        return $total;
    }
}

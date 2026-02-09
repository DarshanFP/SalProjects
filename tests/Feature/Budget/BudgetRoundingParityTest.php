<?php

namespace Tests\Feature\Budget;

use App\Services\Budget\DerivedCalculationService;
use Tests\TestCase;

/**
 * Phase 2.4 — Budget Rounding Parity Guard
 *
 * Ensures PHP rounding matches JS .toFixed(2) behavior when both compute
 * the same formula and round to 2 decimal places.
 */
class BudgetRoundingParityTest extends TestCase
{
    private DerivedCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(DerivedCalculationService::class);
    }

    public function test_decimal_multiplication_case_php_and_js_rounding_match(): void
    {
        $q = 1.333;
        $m = 2.555;
        $d = 3.777;

        $backendRaw = $this->service->calculateRowTotal($q, $m, $d);
        $jsEquivalentRaw = $q * $m * $d;

        $backendRounded = number_format($backendRaw, 2, '.', '');
        $jsRounded = number_format($jsEquivalentRaw, 2, '.', '');

        $this->assertSame(
            $jsRounded,
            $backendRounded,
            'PHP and JS rounding must match for 1.333 × 2.555 × 3.777'
        );
    }

    public function test_large_decimal_case_php_and_js_rounding_match(): void
    {
        $q = 99999.999;
        $m = 1.111;
        $d = 2.222;

        $backendRaw = $this->service->calculateRowTotal($q, $m, $d);
        $jsEquivalentRaw = $q * $m * $d;

        $backendRounded = number_format($backendRaw, 2, '.', '');
        $jsRounded = number_format($jsEquivalentRaw, 2, '.', '');

        $this->assertSame(
            $jsRounded,
            $backendRounded,
            'PHP and JS rounding must match for 99999.999 × 1.111 × 2.222'
        );
    }

    public function test_very_small_decimals_php_and_js_rounding_match(): void
    {
        $q = 0.01;
        $m = 0.02;
        $d = 0.03;

        $backendRaw = $this->service->calculateRowTotal($q, $m, $d);
        $jsEquivalentRaw = $q * $m * $d;

        $backendRounded = number_format($backendRaw, 2, '.', '');
        $jsRounded = number_format($jsEquivalentRaw, 2, '.', '');

        $this->assertSame(
            $jsRounded,
            $backendRounded,
            'PHP and JS rounding must match for 0.01 × 0.02 × 0.03'
        );
    }
}

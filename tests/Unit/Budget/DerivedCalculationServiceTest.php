<?php

namespace Tests\Unit\Budget;

use App\Services\Budget\DerivedCalculationService;
use PHPUnit\Framework\TestCase;

class DerivedCalculationServiceTest extends TestCase
{
    private DerivedCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DerivedCalculationService();
    }

    public function test_single_row_calculation(): void
    {
        $result = $this->service->calculateRowTotal(2.0, 3.0, 4.0);
        $this->assertEquals(24.0, $result, '2 × 3 × 4 must equal 24');
    }

    public function test_decimal_handling(): void
    {
        $result = $this->service->calculateRowTotal(1.5, 2.0, 3.0);
        $this->assertEquals(9.0, $result, '1.5 × 2 × 3 must equal 9.0');
    }

    public function test_multiple_rows_phase_total(): void
    {
        $rows = [
            ['rate_quantity' => 2, 'rate_multiplier' => 3, 'rate_duration' => 4],
            ['rate_quantity' => 1, 'rate_multiplier' => 5, 'rate_duration' => 2],
        ];
        $result = $this->service->calculatePhaseTotal($rows);
        $this->assertEquals(34.0, $result, 'Row1: 2×3×4=24, Row2: 1×5×2=10, Total must equal 34');
    }

    public function test_empty_array_returns_zero(): void
    {
        $result = $this->service->calculatePhaseTotal([]);
        $this->assertSame(0.0, $result, 'Empty rows must return 0.0');
    }

    public function test_zero_multiplier_returns_zero(): void
    {
        $result = $this->service->calculateRowTotal(100.0, 0.0, 10.0);
        $this->assertSame(0.0, $result, 'Zero multiplier must return 0');
    }

    public function test_large_numbers_multiplication(): void
    {
        $result = $this->service->calculateRowTotal(10000.0, 1000.0, 100.0);
        $this->assertEquals(1000000000.0, $result, '10000 × 1000 × 100 must equal 1000000000');
    }
}

<?php

namespace Tests\Unit\Budget;

use App\Services\Budget\DerivedCalculationService;
use Tests\TestCase;

class DerivedCalculationServicePhaseTest extends TestCase
{
    private DerivedCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(DerivedCalculationService::class);
    }

    public function test_phase_total_with_three_rows(): void
    {
        $rows = [
            ['rate_quantity' => 2, 'rate_multiplier' => 3, 'rate_duration' => 4],
            ['rate_quantity' => 1, 'rate_multiplier' => 5, 'rate_duration' => 2],
            ['rate_quantity' => 10, 'rate_multiplier' => 1, 'rate_duration' => 1],
        ];
        $result = $this->service->calculatePhaseTotal($rows);
        $this->assertEquals(44.0, $result, '2×3×4=24, 1×5×2=10, 10×1×1=10, Total=44');
    }

    public function test_phase_total_with_empty_array(): void
    {
        $result = $this->service->calculatePhaseTotal([]);
        $this->assertSame(0.0, $result, 'Empty iterable must return 0.0');
    }

    public function test_phase_total_with_decimals(): void
    {
        $rows = [
            ['rate_quantity' => 1.5, 'rate_multiplier' => 2.0, 'rate_duration' => 3.0],
            ['rate_quantity' => 0.5, 'rate_multiplier' => 4.0, 'rate_duration' => 2.0],
        ];
        $result = $this->service->calculatePhaseTotal($rows);
        $this->assertEquals(13.0, $result, '1.5×2×3=9, 0.5×4×2=4, Total=13');
    }

    public function test_project_total_from_multiple_phase_row_collections(): void
    {
        $phase1 = [
            ['rate_quantity' => 2, 'rate_multiplier' => 3, 'rate_duration' => 4],
            ['rate_quantity' => 1, 'rate_multiplier' => 5, 'rate_duration' => 2],
        ];
        $phase2 = [
            ['rate_quantity' => 10, 'rate_multiplier' => 1, 'rate_duration' => 1],
        ];
        $phases = [$phase1, $phase2];
        $result = $this->service->calculateProjectTotal($phases);
        $this->assertEquals(44.0, $result, 'Phase1: 24+10=34, Phase2: 10, Total=44');
    }

    public function test_project_total_from_numeric_phase_totals(): void
    {
        $phaseTotals = [1000.0, 2000.0, 3000.0];
        $result = $this->service->calculateProjectTotal($phaseTotals);
        $this->assertEquals(6000.0, $result, '1000+2000+3000=6000');
    }

    public function test_project_total_with_empty_iterable(): void
    {
        $result = $this->service->calculateProjectTotal([]);
        $this->assertSame(0.0, $result, 'Empty iterable must return 0.0');
    }

    public function test_phase_total_with_row_objects(): void
    {
        $rows = [
            (object) ['rate_quantity' => 2, 'rate_multiplier' => 3, 'rate_duration' => 4],
            (object) ['rate_quantity' => 1, 'rate_multiplier' => 5, 'rate_duration' => 2],
        ];
        $result = $this->service->calculatePhaseTotal($rows);
        $this->assertEquals(34.0, $result, 'Objects: 2×3×4=24, 1×5×2=10, Total=34');
    }

    public function test_very_large_values_boundary_case(): void
    {
        $rows = [
            ['rate_quantity' => 1000000.0, 'rate_multiplier' => 1000.0, 'rate_duration' => 1.0],
        ];
        $result = $this->service->calculatePhaseTotal($rows);
        $this->assertEquals(1000000000.0, $result, '1000000×1000×1=1000000000');
    }
}

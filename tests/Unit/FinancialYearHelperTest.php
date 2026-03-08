<?php

namespace Tests\Unit;

use App\Models\OldProjects\Project;
use App\Support\FinancialYearHelper;
use Carbon\Carbon;
use Tests\TestCase;

class FinancialYearHelperTest extends TestCase
{
    /**
     * fromDate: 2024-08-01 → 2024-25
     */
    public function test_from_date_august_returns_2024_25(): void
    {
        $date = Carbon::create(2024, 8, 1);
        $this->assertSame('2024-25', FinancialYearHelper::fromDate($date));
    }

    /**
     * fromDate: 2024-02-10 → 2023-24
     */
    public function test_from_date_february_returns_2023_24(): void
    {
        $date = Carbon::create(2024, 2, 10);
        $this->assertSame('2023-24', FinancialYearHelper::fromDate($date));
    }

    /**
     * startDate: "2024-25" → 2024-04-01
     */
    public function test_start_date_returns_april_first(): void
    {
        $start = FinancialYearHelper::startDate('2024-25');
        $this->assertSame(2024, $start->year);
        $this->assertSame(4, $start->month);
        $this->assertSame(1, $start->day);
        $this->assertSame('2024-04-01', $start->format('Y-m-d'));
    }

    /**
     * endDate: "2024-25" → 2025-03-31
     */
    public function test_end_date_returns_march_31(): void
    {
        $end = FinancialYearHelper::endDate('2024-25');
        $this->assertSame(2025, $end->year);
        $this->assertSame(3, $end->month);
        $this->assertSame(31, $end->day);
        $this->assertSame('2025-03-31', $end->format('Y-m-d'));
    }

    /**
     * currentFY: returns correct FY format "YYYY-YY"
     */
    public function test_current_fy_returns_valid_format(): void
    {
        $fy = FinancialYearHelper::currentFY();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $fy);
        $parts = explode('-', $fy);
        $this->assertCount(2, $parts);
        $this->assertSame(2, strlen($parts[1]));
    }

    /**
     * scopeInFinancialYear: generated query contains WHERE commencement_month_year BETWEEN
     */
    public function test_scope_in_financial_year_adds_between_clause(): void
    {
        $query = Project::query()->inFinancialYear('2024-25');
        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('commencement_month_year', $sql);
        $this->assertStringContainsString('between', $sql);
    }

    /**
     * listAvailableFY returns array of FY strings
     */
    public function test_list_available_fy_returns_array(): void
    {
        $list = FinancialYearHelper::listAvailableFY(5);
        $this->assertIsArray($list);
        $this->assertCount(5, $list);
        foreach ($list as $fy) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $fy);
        }
    }
}

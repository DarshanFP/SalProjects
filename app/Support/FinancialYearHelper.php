<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Financial Year Helper — India FY rules (1 April → 31 March).
 *
 * FY label format: "YYYY-YY" (e.g. "2024-25").
 * Used for dashboard filters and project commencement mapping.
 */
class FinancialYearHelper
{
    /**
     * Returns the current financial year string (e.g. "2025-26") based on today's date.
     * Used as default for FY dropdown.
     *
     * @return string FY label in format "YYYY-YY"
     */
    public static function currentFY(): string
    {
        return self::fromDate(Carbon::today());
    }

    /**
     * Returns the next financial year after current (e.g. current 2025-26 → 2026-27).
     * Used as default for pending project list.
     *
     * @return string FY label in format "YYYY-YY"
     */
    public static function nextFY(): string
    {
        $current = self::currentFY();
        $startYear = (int) explode('-', $current)[0];

        return $startYear + 1 . '-' . substr((string) ($startYear + 2), -2);
    }

    /**
     * Derives FY string from any date (e.g. project's commencement_month_year).
     * Core logic for project→FY mapping.
     *
     * Rule: month >= 4 → FY = year-(year+1); else FY = (year-1)-year
     * Examples: 2024-08-01 → "2024-25"; 2024-02-10 → "2023-24"
     *
     * @param Carbon $date Input date
     * @return string FY label in format "YYYY-YY"
     */
    public static function fromDate(Carbon $date): string
    {
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');

        if ($month >= 4) {
            $startYear = $year;
        } else {
            $startYear = $year - 1;
        }

        $endYear = $startYear + 1;
        $endYearShort = substr((string) $endYear, -2);

        return "{$startYear}-{$endYearShort}";
    }

    /**
     * Given FY string "2024-25", returns 1 April 2024 (start of FY).
     * Used for query range start.
     *
     * @param string $fy FY label in format "YYYY-YY"
     * @return Carbon Start of financial year (00:00:00)
     */
    public static function startDate(string $fy): Carbon
    {
        $startYear = (int) explode('-', $fy)[0];

        return Carbon::create($startYear, 4, 1, 0, 0, 0);
    }

    /**
     * Given FY string "2024-25", returns 31 March 2025 (end of FY).
     * Used for query range end.
     *
     * @param string $fy FY label in format "YYYY-YY"
     * @return Carbon End of financial year (23:59:59 for BETWEEN inclusivity)
     */
    public static function endDate(string $fy): Carbon
    {
        $parts = explode('-', $fy);
        $startYear = (int) $parts[0];
        $endYear = $startYear + 1;

        return Carbon::create($endYear, 3, 31, 23, 59, 59);
    }

    /**
     * Returns list of FY strings for dropdown (e.g. last N years including current).
     * Config-driven to avoid N+1 on first load.
     *
     * @param int $yearsBack Number of years to include (default 10)
     * @return array<string> List of FY labels, newest first
     */
    public static function listAvailableFY(int $yearsBack = 10): array
    {
        $currentFY = self::currentFY();
        $startYear = (int) explode('-', $currentFY)[0];
        $result = [];

        for ($i = 0; $i < $yearsBack; $i++) {
            $year = $startYear - $i;
            $endYearShort = substr((string) ($year + 1), -2);
            $result[] = "{$year}-{$endYearShort}";
        }

        return $result;
    }

    /**
     * Derives list of FY strings from project dates (commencement_month_year).
     * Used for dynamic FY dropdown when scope/dataset is scoped (e.g. Executor-owned projects).
     * Falls back to listAvailableFY() when no project dates exist, unless $useStaticFallback is false.
     *
     * @param Builder $projectQuery Project query builder (e.g. from ProjectQueryService)
     * @param bool $useStaticFallback When true and no project dates exist, return listAvailableFY(). When false, return [].
     * @return array<string> List of FY labels, newest first
     */
    public static function listAvailableFYFromProjects(Builder $projectQuery, bool $useStaticFallback = true): array
    {
        $dates = (clone $projectQuery)
            ->whereNotNull('commencement_month_year')
            ->distinct()
            ->pluck('commencement_month_year');

        $fyList = [];
        foreach ($dates as $date) {
            $fyList[] = self::fromDate(Carbon::parse($date));
        }
        $fyList = array_values(array_unique($fyList));
        rsort($fyList);

        if (empty($fyList)) {
            return $useStaticFallback ? self::listAvailableFY() : [];
        }

        return $fyList;
    }
}

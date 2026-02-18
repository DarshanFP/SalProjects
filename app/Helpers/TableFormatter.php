<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Pure table-formatting and aggregation logic for data/financial tables.
 * No DB calls, no side effects. Used by DataTable and FinancialTable components.
 */
class TableFormatter
{
    /** @var array<int> Allowed per-page values for page size selector. */
    public const ALLOWED_PAGE_SIZES = [10, 25, 50, 100];

    /**
     * Format a value as currency using project-standard (Indian format with Rs.).
     * Null-safe.
     *
     * @param float|int|null $value
     * @param int $decimals
     * @return string
     */
    public static function formatCurrency($value, int $decimals = 2): string
    {
        $num = $value === null || $value === '' ? 0 : (float) $value;
        return NumberFormatHelper::formatIndianCurrency($num, $decimals);
    }

    /**
     * Format a value as a plain number (Indian style, no currency symbol).
     * Null-safe.
     *
     * @param float|int|null $value
     * @param int $decimals
     * @return string
     */
    public static function formatNumber($value, int $decimals = 0): string
    {
        $num = $value === null || $value === '' ? 0 : (float) $value;
        return NumberFormatHelper::formatIndian($num, $decimals);
    }

    /**
     * Sum a single column on a collection. Safe for non-numeric values (casts to float).
     *
     * @param Collection $collection
     * @param string $column Attribute/key to sum (e.g. 'amount_sanctioned')
     * @return float
     */
    public static function calculateTotal(Collection $collection, string $column): float
    {
        return (float) $collection->sum(function ($item) use ($column) {
            $val = is_array($item) ? ($item[$column] ?? 0) : ($item->{$column} ?? 0);
            return $val === null || $val === '' ? 0 : (float) $val;
        });
    }

    /**
     * Sum multiple columns on a collection. Returns keyed array of totals.
     *
     * @param Collection $collection
     * @param array $columns List of column keys (e.g. ['amount_sanctioned', 'balance_amount'])
     * @return array<string, float>
     */
    public static function calculateMultipleTotals(Collection $collection, array $columns): array
    {
        $result = [];
        foreach ($columns as $column) {
            $result[$column] = self::calculateTotal($collection, $column);
        }
        return $result;
    }

    /**
     * Resolve serial number for a row (S.No.).
     * Paginated: continues across pages (e.g. page 2 starts at 11 if perPage=10).
     * Non-paginated: 1-based iteration.
     *
     * @param \Illuminate\Support\HtmlString|object $loop Blade $loop (iteration, etc.)
     * @param Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator|null $collection For paginated, must have firstItem()
     * @param bool $paginated Whether the list is paginated
     * @return int
     */
    public static function resolveSerial($loop, $collection = null, bool $paginated = false): int
    {
        if (!$paginated || $collection === null) {
            return (int) ($loop->iteration ?? 1);
        }

        $firstItem = method_exists($collection, 'firstItem') ? $collection->firstItem() : 1;
        $iteration = (int) ($loop->iteration ?? 1);

        return $firstItem + $iteration - 1;
    }

    /**
     * Project show URL for linkable project ID column.
     * Uses route 'projects.show'; no DB calls.
     *
     * @param string|int $projectId
     * @return string
     */
    public static function projectLink($projectId): string
    {
        return route('projects.show', $projectId);
    }

    /**
     * Resolve a single grand total from controller-provided totals.
     * Do NOT compute full-dataset sum inside component when paginated (performance).
     *
     * @param array<string, float> $totalsFromController Keyed by column name
     * @param string|null $column Column key
     * @return float
     */
    public static function resolveGrandTotal(array $totalsFromController = [], ?string $column = null): float
    {
        if ($column === null) {
            return 0.0;
        }
        return (float) ($totalsFromController[$column] ?? 0);
    }

    /**
     * Return safe grand totals array from controller.
     * Use for summary block when paginated; avoids summing full dataset in view.
     *
     * @param array<string, float> $controllerTotals
     * @return array<string, float>
     */
    public static function resolveGrandTotals(array $controllerTotals = []): array
    {
        $out = [];
        foreach ($controllerTotals as $key => $value) {
            $out[$key] = (float) $value;
        }
        return $out;
    }

    /**
     * Resolve total record count: controller-provided when paginated, else collection count/total.
     * No DB calls.
     *
     * @param \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator $collection
     * @param int|null $totalFromController
     * @return int
     */
    public static function resolveTotalRecordCount($collection, ?int $totalFromController = null): int
    {
        if ($totalFromController !== null && $totalFromController >= 0) {
            return $totalFromController;
        }
        if (method_exists($collection, 'total')) {
            return (int) $collection->total();
        }
        return $collection->count();
    }

    /**
     * Resolve safe per-page value from request for pagination.
     * Validates against allowed sizes; never allows arbitrary large values.
     * No DB calls.
     *
     * @param Request|null $request Defaults to request() when null
     * @param int $default Fallback when missing or invalid (should be one of ALLOWED_PAGE_SIZES)
     * @return int
     */
    public static function resolvePerPage(?Request $request = null, int $default = 25): int
    {
        $request = $request ?? request();
        $allowed = self::ALLOWED_PAGE_SIZES;
        $requested = $request->query('per_page');

        if ($requested === null || $requested === '') {
            return in_array($default, $allowed, true) ? $default : $allowed[0];
        }

        $value = (int) $requested;
        if ($value < 1 || !in_array($value, $allowed, true)) {
            return in_array($default, $allowed, true) ? $default : $allowed[0];
        }

        return $value;
    }
}

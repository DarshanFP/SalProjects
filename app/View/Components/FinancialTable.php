<?php

namespace App\View\Components;

use App\Helpers\TableFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class FinancialTable extends Component
{
    /** @var Collection */
    public $collection;

    /** @var array<int, array{key: string, label: string, numeric?: bool}> */
    public $columns;

    /** @var bool */
    public $serial;

    /** @var bool */
    public $paginated;

    /** @var bool */
    public $showTotals;

    /** @var array */
    public $numericColumnKeys;

    /** @var bool */
    public $showSummary;

    /** @var array<string, float> */
    public $grandTotals;

    /** @var int|null */
    public $totalRecordCount;

    /** @var string|null */
    public $projectIdColumn;

    /** @var bool */
    public $linkProjectId;

    /** @var bool */
    public $allowPageSizeSelector;

    /** @var int */
    public $currentPerPage;

    /** @var array<int> */
    public $allowedPageSizes;

    /** @var bool */
    public $allowExport;

    /** @var string|null */
    public $exportRoute;

    /**
     * @param Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator $collection
     * @param array $columns [['key' => 'particular', 'label' => 'Particulars'], ['key' => 'amount_sanctioned', 'label' => 'Amount Sanctioned', 'numeric' => true], ...]
     * @param bool $serial
     * @param bool $paginated
     * @param bool $showTotals
     * @param bool $showSummary
     * @param array $grandTotals Controller-calculated grand totals when paginated (keyed by column)
     * @param int|null $totalRecordCount Controller-provided total count when paginated
     * @param string|null $projectIdColumn Column key to render as project link (e.g. 'project_id')
     * @param bool $linkProjectId
     * @param bool $allowPageSizeSelector
     * @param int $currentPerPage Current per_page value (from TableFormatter::resolvePerPage)
     * @param array $allowedPageSizes Allowed options (default TableFormatter::ALLOWED_PAGE_SIZES)
     * @param bool $allowExport
     * @param string|null $exportRoute Route name for Excel export (must accept query params for filters)
     */
    public function __construct(
        $collection,
        array $columns = [],
        bool $serial = true,
        bool $paginated = false,
        bool $showTotals = true,
        bool $showSummary = false,
        array $grandTotals = [],
        ?int $totalRecordCount = null,
        ?string $projectIdColumn = null,
        bool $linkProjectId = false,
        bool $allowPageSizeSelector = false,
        int $currentPerPage = 25,
        array $allowedPageSizes = [],
        bool $allowExport = false,
        ?string $exportRoute = null
    ) {
        $this->collection = $collection instanceof Collection ? $collection : collect($collection);
        $this->columns = $columns;
        $this->serial = $serial;
        $this->paginated = $paginated;
        $this->showTotals = $showTotals;
        $this->showSummary = $showSummary;
        $this->grandTotals = TableFormatter::resolveGrandTotals($grandTotals);
        $this->totalRecordCount = $totalRecordCount;
        $this->projectIdColumn = $projectIdColumn;
        $this->linkProjectId = $linkProjectId && $projectIdColumn !== null && $projectIdColumn !== '';
        $this->allowPageSizeSelector = $allowPageSizeSelector && $paginated;
        $this->currentPerPage = $currentPerPage > 0 ? $currentPerPage : 25;
        $this->allowedPageSizes = $allowedPageSizes !== [] ? $allowedPageSizes : TableFormatter::ALLOWED_PAGE_SIZES;
        $this->allowExport = $allowExport && $exportRoute !== null && $exportRoute !== '';
        $this->exportRoute = $exportRoute;

        $this->numericColumnKeys = array_values(array_map(
            fn ($col) => $col['key'],
            array_filter($columns, fn ($c) => ($c['numeric'] ?? false) === true)
        ));
    }

    public function totals(): array
    {
        if (!$this->showTotals || count($this->numericColumnKeys) === 0) {
            return [];
        }
        return TableFormatter::calculateMultipleTotals($this->collection, $this->numericColumnKeys);
    }

    /**
     * Grand totals for summary block. When paginated, use controller-provided only; do not sum full dataset.
     */
    public function summaryTotals(): array
    {
        if (!$this->showSummary) {
            return [];
        }
        if (count($this->grandTotals) > 0) {
            return $this->grandTotals;
        }
        if ($this->paginated) {
            // Developer-level: do not auto-calc full dataset when paginated; controller must pass grandTotals.
            return [];
        }
        return TableFormatter::calculateMultipleTotals($this->collection, $this->numericColumnKeys);
    }

    public function resolvedRecordCount(): int
    {
        return TableFormatter::resolveTotalRecordCount($this->collection, $this->totalRecordCount);
    }

    public function render(): View
    {
        return view('components.financial-table');
    }
}

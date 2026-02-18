<?php

namespace App\View\Components;

use App\Helpers\TableFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class DataTable extends Component
{
    /** @var Collection */
    public $collection;

    /** @var bool */
    public $paginated;

    /** @var bool */
    public $serial;

    /** @var array */
    public $numericColumns;

    /** @var bool */
    public $showTotals;

    /**
     * @param Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator $collection
     * @param bool $paginated
     * @param bool $serial
     * @param array $numericColumns Column keys to sum in footer (e.g. ['amount_sanctioned'])
     * @param bool $showTotals
     */
    public function __construct(
        $collection,
        bool $paginated = false,
        bool $serial = true,
        array $numericColumns = [],
        bool $showTotals = false
    ) {
        $this->collection = $collection instanceof Collection ? $collection : collect($collection);
        $this->paginated = $paginated;
        $this->serial = $serial;
        $this->numericColumns = $numericColumns;
        $this->showTotals = $showTotals && count($numericColumns) > 0;
    }

    /**
     * Totals for footer (key => summed value).
     */
    public function totals(): array
    {
        if (!$this->showTotals) {
            return [];
        }
        return TableFormatter::calculateMultipleTotals($this->collection, $this->numericColumns);
    }

    public function render(): View
    {
        return view('components.data-table');
    }
}

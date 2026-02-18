@props([
    'collection' => collect(),
    'columns' => [],
    'serial' => true,
    'paginated' => false,
    'showTotals' => true,
    'showSummary' => false,
    'grandTotals' => [],
    'totalRecordCount' => null,
    'projectIdColumn' => null,
    'linkProjectId' => false,
    'allowPageSizeSelector' => false,
    'currentPerPage' => 25,
    'allowedPageSizes' => [10, 25, 50, 100],
    'allowExport' => false,
    'exportRoute' => null,
])

@php
    $numericKeys = array_values(array_map(
        fn ($c) => $c['key'],
        array_filter($columns, fn ($c) => ($c['numeric'] ?? false) === true)
    ));
    $totals = $showTotals && count($numericKeys) > 0
        ? \App\Helpers\TableFormatter::calculateMultipleTotals($collection, $numericKeys)
        : [];
    $summaryTotals = [];
    if ($showSummary) {
        if (count($grandTotals ?? []) > 0) {
            $summaryTotals = \App\Helpers\TableFormatter::resolveGrandTotals($grandTotals);
        } elseif (!$paginated && count($numericKeys) > 0) {
            $summaryTotals = \App\Helpers\TableFormatter::calculateMultipleTotals($collection, $numericKeys);
        }
        // When paginated and no grandTotals provided: do not auto-calc full dataset; controller must pass grandTotals.
    }
    $resolvedRecordCount = $showSummary ? \App\Helpers\TableFormatter::resolveTotalRecordCount($collection, $totalRecordCount ?? null) : 0;
    $summaryLabels = collect($columns)->pluck('label', 'key')->filter()->all();
@endphp

@if($showSummary && ($resolvedRecordCount > 0 || count($summaryTotals) > 0))
    <x-table-summary
        :totalRecordCount="$resolvedRecordCount"
        :totals="$summaryTotals"
        :labels="$summaryLabels"
        class="table-summary-block"
    />
@endif

@if($allowPageSizeSelector || $allowExport)
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        @if($allowPageSizeSelector)
            <form method="get" action="{{ request()->url() }}" class="d-flex align-items-center gap-2" id="table-per-page-form">
                @foreach(request()->except(['per_page', 'page']) as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $v)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="table-per-page-select" class="form-label mb-0 small text-muted">Rows per page:</label>
                <select name="per_page" id="table-per-page-select" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    @foreach($allowedPageSizes as $size)
                        <option value="{{ $size }}" {{ (int) $currentPerPage === (int) $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </form>
        @endif
        @if($allowExport && $exportRoute)
            <a href="{{ route($exportRoute, request()->query()) }}" class="btn btn-success btn-sm">
                Download Excel
            </a>
        @endif
    </div>
@endif

<table {{ $attributes->merge(['class' => 'table table-bordered']) }}>
    <thead class="table-light">
        <tr>
            @if($serial)
                <th scope="col" style="min-width: 3rem;">S.No.</th>
            @endif
            @foreach($columns as $col)
                <th scope="col" class="{{ ($col['numeric'] ?? false) ? 'text-end' : '' }}">
                    {{ $col['label'] ?? $col['key'] }}
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($collection as $index => $item)
            <tr>
                @if($serial)
                    <td class="text-center">
                        {{ \App\Helpers\TableFormatter::resolveSerial($loop, $collection, $paginated) }}
                    </td>
                @endif
                @foreach($columns as $col)
                    @php
                        $key = $col['key'];
                        $numeric = $col['numeric'] ?? false;
                        $val = is_array($item) ? ($item[$key] ?? null) : ($item->{$key} ?? null);
                        $isProjectIdColumn = $linkProjectId && $projectIdColumn !== null && $key === $projectIdColumn;
                    @endphp
                    <td class="{{ $numeric ? 'text-end' : '' }}">
                        @if($numeric)
                            {{ \App\Helpers\TableFormatter::formatCurrency($val, 2) }}
                        @elseif($isProjectIdColumn && $val)
                            <a href="{{ \App\Helpers\TableFormatter::projectLink($val) }}" class="fw-semibold text-primary text-decoration-none">{{ $val }}</a>
                        @else
                            {{ $val ?? '—' }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
    @if(count($totals) > 0)
        <tfoot class="table-light" style="border-top: 2px solid #dee2e6;">
            <tr>
                @if($serial)
                    <td></td>
                @endif
                @php $totalLabelShown = false; @endphp
                @foreach($columns as $col)
                    @if($col['numeric'] ?? false)
                        <td class="text-end fw-bold">
                            {{ \App\Helpers\TableFormatter::formatCurrency($totals[$col['key']] ?? 0, 2) }}
                        </td>
                    @else
                        <td class="fw-bold">{{ !$totalLabelShown ? 'Total' : '' }}</td>
                        @php $totalLabelShown = true; @endphp
                    @endif
                @endforeach
            </tr>
        </tfoot>
    @endif
</table>

@props([
    'totalRecordCount' => 0,
    'totals' => [],
    'labels' => [],
])

@php
    $labels = $labels ?: [];
@endphp

<div class="d-flex flex-wrap align-items-center gap-3 p-3 mb-3 border rounded bg-light" {{ $attributes }}>
    <span class="fw-semibold">Total Records: {{ (int) $totalRecordCount }}</span>
    @foreach($totals as $columnKey => $value)
        <span class="fw-semibold">
            {{ $labels[$columnKey] ?? ucfirst(str_replace('_', ' ', $columnKey)) }}: {{ \App\Helpers\TableFormatter::formatCurrency($value, 2) }}
        </span>
    @endforeach
</div>

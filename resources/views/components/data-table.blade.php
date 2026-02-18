@props([
    'collection' => collect(),
    'paginated' => false,
    'serial' => true,
    'numericColumns' => [],
    'showTotals' => false,
])

@php
    $totals = $showTotals && count($numericColumns) > 0
        ? \App\Helpers\TableFormatter::calculateMultipleTotals($collection, $numericColumns)
        : [];
@endphp

<table {{ $attributes->merge(['class' => 'table table-bordered']) }}>
    <thead>
        <tr>
            @if($serial)
                <th scope="col" style="min-width: 3rem;">S.No.</th>
            @endif
            {{ $head ?? '' }}
        </tr>
    </thead>
    <tbody>
        {{ $body ?? $slot }}
    </tbody>
    @if(count($totals) > 0)
        <tfoot class="table-light" style="border-top: 2px solid #dee2e6;">
            <tr>
                @if($serial)
                    <td></td>
                @endif
                <td class="fw-bold">Total</td>
                @foreach($numericColumns as $col)
                    <td class="text-end fw-bold">
                        {{ \App\Helpers\TableFormatter::formatCurrency($totals[$col] ?? 0, 2) }}
                    </td>
                @endforeach
            </tr>
        </tfoot>
    @elseif(isset($footer))
        <tfoot class="table-light" style="border-top: 2px solid #dee2e6;">
            {{ $footer }}
        </tfoot>
    @endif
</table>

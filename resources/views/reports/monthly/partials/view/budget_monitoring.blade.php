{{-- Provincial budget monitoring: overspend, negative balance, utilisation summary and alerts. --}}
@php
    $overspend = $budgetOverspendRows ?? [];
    $negative = $budgetNegativeBalanceRows ?? [];
    $utilisation = $budgetUtilisation ?? ['total_sanctioned' => 0, 'total_expenses' => 0, 'utilisation_percent' => 0, 'alerts' => []];
    $showStatuses = ['submitted_to_provincial', 'forwarded_to_coordinator'];
    $showByStatus = in_array($report->status ?? '', $showStatuses);
    $showByRole = in_array(auth()->user()->role ?? '', ['provincial', 'coordinator']);
@endphp
@if($showByStatus && $showByRole)
<div class="mb-3 card border-warning">
    <div class="card-header bg-warning bg-opacity-25">
        <h4 class="fp-text-margin mb-0">Budget — Monitoring</h4>
    </div>
    <div class="card-body">
        {{-- Utilisation summary --}}
        <div class="mb-4">
            <h5 class="text-secondary">Utilisation summary</h5>
            <div class="info-grid" style="grid-template-columns: 20% 80%;">
                <div class="info-label"><strong>Total sanctioned:</strong></div>
                <div class="info-value">{{ format_indian_currency($utilisation['total_sanctioned'] ?? 0, 2) }}</div>
                <div class="info-label"><strong>Total expenses:</strong></div>
                <div class="info-value">{{ format_indian_currency($utilisation['total_expenses'] ?? 0, 2) }}</div>
                <div class="info-label"><strong>Utilisation:</strong></div>
                <div class="info-value">{{ format_indian_percentage($utilisation['utilisation_percent'] ?? 0, 1) }}</div>
            </div>
        </div>

        {{-- One-line alert messages --}}
        @php $alerts = $utilisation['alerts'] ?? []; @endphp
        @if(count($alerts) > 0)
        <div class="mb-4">
            <h5 class="text-secondary">Alerts</h5>
            <ul class="mb-0">
                @if(in_array('high_utilization', $alerts))
                    <li>Utilisation above 90%.</li>
                @endif
                @if(in_array('negative_balance', $alerts))
                    <li>Negative balance on one or more heads.</li>
                @endif
                @if(in_array('overspend_row', $alerts))
                    <li>Overspend on one or more budget heads.</li>
                @endif
                @if(in_array('high_expenses_this_month', $alerts))
                    <li>Unusually high spend this month vs last month — please confirm.</li>
                @endif
            </ul>
        </div>
        @endif

        @if(count($overspend) > 0)
        <div class="mb-4">
            <h5 class="text-secondary">Overspend (total_expenses &gt; amount sanctioned / total amount)</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Particulars</th>
                            <th>Amount sanctioned</th>
                            <th>Total expenses</th>
                            <th>Excess</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overspend as $row)
                        <tr>
                            <td>{{ $row['particulars'] ?: '—' }}</td>
                            <td>{{ format_indian_currency($row['amount_sanctioned'] ?? 0, 2) }}</td>
                            <td>{{ format_indian_currency($row['total_expenses'] ?? 0, 2) }}</td>
                            <td class="text-danger">{{ format_indian_currency($row['excess'] ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if(count($negative) > 0)
        <div>
            <h5 class="text-secondary">Negative balance</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Particulars</th>
                            <th>Balance amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($negative as $row)
                        <tr>
                            <td>{{ $row['particulars'] ?: '—' }}</td>
                            <td class="text-danger">{{ format_indian_currency($row['balance_amount'] ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endif

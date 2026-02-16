{{-- resources/views/projects/partials/Show/ILP/budget.blade.php --}}
@php
    $budgets = collect(($ILPBudgets ?? [])['budgets'] ?? []);
@endphp

<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Budget Details</h4>
    </div>
    <div class="card-body">
        @if ($budgets instanceof \Illuminate\Support\Collection && $budgets->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 5%;">Sl No</th>
                            <th>Description</th>
                            <th>Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($budgets as $index => $budget)
                            <tr>
                                <td style="text-align: center; vertical-align: middle;">{{ $index + 1 }}</td>
                                <td>{{ $budget->budget_desc ?? 'N/A' }}</td>
                                <td>{{ format_indian_currency($budget->cost ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <div class="mb-3">
                    <span class="info-label fw-bold">Total amount:</span>
                    <span class="info-value">{{ format_indian_currency(($ILPBudgets ?? [])['total_amount'] ?? 0, 2) }}</span>
                </div>
                <div class="mb-3">
                    <span class="info-label fw-bold">Beneficiary's contribution:</span>
                    <span class="info-value">{{ format_indian_currency(($ILPBudgets ?? [])['beneficiary_contribution'] ?? 0, 2) }}</span>
                </div>
                <div class="mb-3">
                    <span class="info-label fw-bold">Amount requested:</span>
                    <span class="info-value">{{ format_indian_currency(($ILPBudgets ?? [])['amount_requested'] ?? 0, 2) }}</span>
                </div>
            </div>
        @else
            <p class="text-muted">No budget items recorded.</p>
        @endif
    </div>
</div>

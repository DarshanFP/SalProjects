{{-- resources/views/projects/partials/Show/IES/estimated_expenses.blade.php --}}
@php
    $iesExpenses = $project->iesExpenses?->first() ?? null;
    $expenseDetails = $iesExpenses?->expenseDetails ?? collect();
@endphp

<div class="mb-3 card">
    <div class="card-header">
        <h4>Estimated Expenses</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 5%;">Sl No</th>
                        <th>Particular</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @if (isset($expenseDetails) && $expenseDetails instanceof \Illuminate\Support\Collection && $expenseDetails->count() > 0)
                        @foreach ($expenseDetails as $index => $detail)
                            <tr>
                                <td style="text-align: center; vertical-align: middle;">{{ $index + 1 }}</td>
                                <td>{{ $detail->particular ?? 'N/A' }}</td>
                                <td>{{ format_indian_currency($detail->amount ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="3" class="text-center text-muted">No expense details recorded.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if($iesExpenses)
            <div class="mt-3">
                <h5>Total expense of the study: <strong>{{ format_indian_currency($iesExpenses->total_expenses ?? 0, 2) }}</strong></h5>
            </div>

            <div class="mt-3">
                <h6>Expected Govt Scholarship: <strong>{{ format_indian_currency($iesExpenses->expected_scholarship_govt ?? 0, 2) }}</strong></h6>
                <h6>Support from other sources: <strong>{{ format_indian_currency($iesExpenses->support_other_sources ?? 0, 2) }}</strong></h6>
                <h6>Beneficiaries' contribution: <strong>{{ format_indian_currency($iesExpenses->beneficiary_contribution ?? 0, 2) }}</strong></h6>
            </div>

            <div class="mt-3">
                <h5 class="text-danger">Balance Requested: <strong>{{ format_indian_currency($iesExpenses->balance_requested ?? 0, 2) }}</strong></h5>
            </div>
        @else
            <p class="text-muted">No expense details recorded.</p>
        @endif
    </div>
</div>

{{-- resources/views/projects/partials/Show/IIES/estimated_expenses.blade.php --}}
@php
    // Get the IIES expenses data
    $iiesExpenses = $project->iiesExpenses ?? new \App\Models\OldProjects\IIES\ProjectIIESExpenses();
    $expenseDetails = $iiesExpenses->expenseDetails ?? collect();
@endphp

<div class="mb-3 card">
    <div class="card-header">
        <h4>IIES Estimated Expenses</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th>Particular</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($expenseDetails->count())
                        @foreach ($expenseDetails as $index => $detail)
                            <tr>
                                <td style="text-align: center; vertical-align: middle;">{{ $index + 1 }}</td>
                                <td>{{ $detail->iies_particular }}</td>
                                <td>{{ format_indian($detail->iies_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="2" class="text-center">No expense details available.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <h5>Total expense of the study: <strong>{{ format_indian($iiesExpenses->iies_total_expenses ?? 0, 2) }}</strong></h5>
        </div>

        <div class="mt-3">
            <h6>Scholarship expected from government: <strong>{{ format_indian($iiesExpenses->iies_expected_scholarship_govt ?? 0, 2) }}</strong></h6>
            <h6>Support from other sources: <strong>{{ format_indian($iiesExpenses->iies_support_other_sources ?? 0, 2) }}</strong></h6>
            <h6>Beneficiaries’ contribution: <strong>{{ format_indian($iiesExpenses->iies_beneficiary_contribution ?? 0, 2) }}</strong></h6>
        </div>

        <div class="mt-3">
            <h5 class="text-danger">Balance amount requested: <strong>{{ format_indian($iiesExpenses->iies_balance_requested ?? 0, 2) }}</strong></h5>
        </div>
    </div>
</div>

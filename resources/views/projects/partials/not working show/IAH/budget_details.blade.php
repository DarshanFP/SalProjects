{{-- resources/views/projects/partials/Show/IAH/budget_details.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Estimated Cost of Treatment – Budget Details</h4>
    </div>
    <div class="card-body">
        @if($IAHBudgetDetails && $IAHBudgetDetails->count())
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Particular</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($IAHBudgetDetails as $budget)
                            <tr>
                                <td>{{ $budget->particular ?? 'Not provided' }}</td>
                                <td>{{ $budget->amount ? '₹' . number_format($budget->amount, 2) : 'Not provided' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <div class="info-grid">
                    <div class="mb-3">
                        <span class="info-label">Total Expenses:</span>
                        <span class="info-value">₹{{ number_format($IAHBudgetDetails->sum('amount'), 2) }}</span>
                    </div>

                    @if($IAHBudgetDetails->first())
                    <div class="mb-3">
                        <span class="info-label">Family Contribution:</span>
                        <span class="info-value">₹{{ number_format($IAHBudgetDetails->first()->family_contribution ?? 0, 2) }}</span>
                    </div>

                    <div class="mb-3">
                        <span class="info-label">Total Amount Requested:</span>
                        <span class="info-value">₹{{ number_format($IAHBudgetDetails->first()->amount_requested ?? 0, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>
        @else
            <div class="alert alert-info">
                No budget details available.
            </div>
        @endif
    </div>
</div>

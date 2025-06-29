@if($budgets && count($budgets) > 0)
    <div class="section-header">Statements of Account</div>
    <table class="account-table">
        <tr class="header-row">
            <td>Description</td>
            <td>Budget Amount</td>
            <td>Expenditure</td>
            <td>Balance</td>
            <td>Remarks</td>
        </tr>
        @foreach($budgets as $budget)
            <tr class="{{ $budget->is_budget_row ? 'budget-row' : '' }}">
                <td>
                    {{ $budget->description ?? 'N/A' }}
                    @if($budget->is_budget_row)
                        <span class="budget-badge">Budget Row</span>
                    @endif
                </td>
                <td>{{ number_format($budget->budget_amount ?? 0, 2) }}</td>
                <td>{{ number_format($budget->expenditure ?? 0, 2) }}</td>
                <td>{{ number_format(($budget->budget_amount ?? 0) - ($budget->expenditure ?? 0), 2) }}</td>
                <td>{{ $budget->remarks ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </table>
@else
    <div class="section-header">Statements of Account</div>
    <p><em>No budget data found for this report.</em></p>
@endif

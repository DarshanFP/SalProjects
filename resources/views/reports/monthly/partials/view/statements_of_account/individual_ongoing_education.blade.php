{{-- resources/views/reports/monthly/partials/view/statements_of_account/individual_ongoing_education.blade.php --}}
<!-- Account Details Section -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Account Details</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-label"><strong>Account Period:</strong></div>
            <div class="info-value">{{ \Carbon\Carbon::parse($report->account_period_start)->format('d-m-Y') }} to {{ \Carbon\Carbon::parse($report->account_period_end)->format('d-m-Y') }}</div>
            <div class="info-label"><strong>Amount Sanctioned:</strong></div>
            <div class="info-value">Rs. {{ number_format($report->amount_sanctioned_overview, 2) }}</div>
            <div class="info-label"><strong>Amount Forwarded:</strong></div>
            <div class="info-value">Rs. {{ number_format($report->amount_forwarded_overview, 2) }}</div>
            <div class="info-label"><strong>Total Amount:</strong></div>
            <div class="info-value">Rs. {{ number_format($report->amount_in_hand, 2) }}</div>
            <div class="info-label"><strong>Balance Forwarded:</strong></div>
            <div class="info-value">Rs. {{ number_format($report->total_balance_forwarded, 2) }}</div>
        </div>

        <div class="mt-3 table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Particulars</th>
                        <th>Amount Forwarded from Previous Year</th>
                        <th>Amount Sanctioned Current Year</th>
                        <th>Total Amount</th>
                        <th>Expenses Up to Last Month</th>
                        <th>Expenses of This Month</th>
                        <th>Total Expenses</th>
                        <th>Balance Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->accountDetails as $accountDetail)
                        <tr>
                            <td>{{ $accountDetail->particulars }}</td>
                            <td>Rs. {{ number_format($accountDetail->amount_forwarded, 2) }}</td>
                            <td>Rs. {{ number_format($accountDetail->amount_sanctioned, 2) }}</td>
                            <td>Rs. {{ number_format($accountDetail->total_amount, 2) }}</td>
                            <td>Rs. {{ number_format($accountDetail->expenses_last_month, 2) }}</td>
                            <td>Rs. {{ number_format($accountDetail->expenses_this_month, 2) }}</td>
                            <td>Rs. {{ number_format($accountDetail->total_expenses, 2) }}</td>
                            <td class="{{ $accountDetail->balance_amount < 0 ? 'text-danger' : 'text-success' }}">
                                Rs. {{ number_format($accountDetail->balance_amount, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-info">
                    <tr class="table-info font-weight-bold">
                        <td><strong>TOTAL</strong></td>
                        <td><strong>Rs. {{ number_format($report->accountDetails->sum('amount_forwarded'), 2) }}</strong></td>
                        <td><strong>Rs. {{ number_format($report->accountDetails->sum('amount_sanctioned'), 2) }}</strong></td>
                        <td><strong>Rs. {{ number_format($report->accountDetails->sum('total_amount'), 2) }}</strong></td>
                        <td><strong>Rs. {{ number_format($report->accountDetails->sum('expenses_last_month'), 2) }}</strong></td>
                        <td><strong>Rs. {{ number_format($report->accountDetails->sum('expenses_this_month'), 2) }}</strong></td>
                        <td><strong>Rs. {{ number_format($report->accountDetails->sum('total_expenses'), 2) }}</strong></td>
                        <td><strong>Rs. {{ number_format($report->accountDetails->sum('balance_amount'), 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

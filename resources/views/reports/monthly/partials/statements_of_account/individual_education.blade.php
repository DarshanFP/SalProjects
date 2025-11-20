{{-- resources/views/reports/monthly/partials/statements_of_account/individual_education.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>4. Statements of Account</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="account_period" class="form-label">Account Statement Period:</label>
            <div class="d-flex">
                <input type="date" name="account_period_start" class="form-control @error('account_period_start') is-invalid @enderror"
                       value="{{ old('account_period_start', $report->account_period_start ?? '') }}"
                       style="background-color: #202ba3;">
                @error('account_period_start')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <span class="mx-2">to</span>
                <input type="date" name="account_period_end" class="form-control @error('account_period_end') is-invalid @enderror"
                       value="{{ old('account_period_end', $report->account_period_end ?? '') }}"
                       style="background-color: #202ba3;">
                @error('account_period_end')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="amount_sanctioned_overview" class="form-label">Amount Sanctioned: Rs.</label>
            <input type="number" name="amount_sanctioned_overview" class="form-control"
                   value="{{ old('amount_sanctioned_overview', $report->amount_sanctioned_overview ?? $amountSanctioned ?? 0.00) }}" readonly>
        </div>

        <div class="mb-3">
            <label for="amount_forwarded_overview" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
            <input type="number" name="amount_forwarded_overview" class="form-control"
                   value="{{ old('amount_forwarded_overview', $report->amount_forwarded_overview ?? $amountForwarded ?? 0.00) }}"
                   style="background-color: #202ba3;">
        </div>

        <div class="mb-3">
            <label for="amount_in_hand" class="form-label">Total Amount: Rs.</label>
            <input type="number" name="amount_in_hand" class="form-control readonly-input"
                   value="{{ old('amount_in_hand', ($report->amount_in_hand ?? ($amountSanctioned ?? 0.00) + ($amountForwarded ?? 0.00))) }}" readonly>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Particulars</th>
                    <th>Amount Forwarded from the Previous Year</th>
                    <th>Amount Sanctioned Current Year</th>
                    <th>Total Amount (2+3)</th>
                    <th>Expenses Up to Last Month</th>
                    <th>Expenses of This Month</th>
                    <th>Total Expenses (5+6)</th>
                    <th>Balance Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="account-rows">
                @if(isset($report) && $report->accountDetails)
                    {{-- Edit Mode: Use saved account details --}}
                    @foreach ($report->accountDetails as $index => $accountDetail)
                        <tr data-row-type="{{ $accountDetail->is_budget_row ? 'budget' : 'additional' }}">
                            <input type="hidden" name="account_detail_id[{{$index}}]" value="{{ $accountDetail->account_detail_id }}">
                            <input type="hidden" name="is_budget_row[{{$index}}]" value="{{ $accountDetail->is_budget_row ? '1' : '0' }}">
                            <td><input type="text" name="particulars[]" class="form-control" value="{{ old('particulars.' . $index, $accountDetail->particulars) }}" readonly></td>
                            <td><input type="number" name="amount_forwarded[]" class="form-control" value="{{ old('amount_forwarded.' . $index, $accountDetail->amount_forwarded) }}" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
                            <td><input type="number" name="amount_sanctioned[]" class="form-control" value="{{ old('amount_sanctioned.' . $index, $accountDetail->amount_sanctioned) }}" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
                            <td><input type="number" name="total_amount[]" class="form-control" value="{{ old('total_amount.' . $index, $accountDetail->amount_forwarded + $accountDetail->amount_sanctioned) }}" readonly></td>
                            <td><input type="number" name="expenses_last_month[]" class="form-control" value="{{ old('expenses_last_month.' . $index, $accountDetail->expenses_last_month) }}" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
                            <td><input type="number" name="expenses_this_month[]" class="form-control" value="{{ old('expenses_this_month.' . $index, $accountDetail->expenses_this_month) }}" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="total_expenses[]" class="form-control" value="{{ old('total_expenses.' . $index, $accountDetail->total_expenses) }}" readonly></td>
                            <td><input type="number" name="balance_amount[]" class="form-control" value="{{ old('balance_amount.' . $index, $accountDetail->balance_amount) }}" readonly></td>
                            <td>
                                @if(!$accountDetail->is_budget_row)
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeAccountRow(this)">Remove</button>
                                @else
                                    <span class="badge bg-info">Budget Row</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @elseif(isset($budgets))
                    {{-- Create Mode: Use IGE project budgets --}}
                    @foreach($budgets as $index => $budget)
                    <tr data-row-type="budget">
                        <input type="hidden" name="is_budget_row[{{$index}}]" value="1">
                        <td><input type="text" name="particulars[]" class="form-control" value="{{ old('particulars.'.$index, $budget->iies_particular) }}" readonly></td>
                        <td><input type="number" name="amount_forwarded[]" class="form-control" value="{{ old('amount_forwarded.'.$index, 0.00) }}" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;"></td>
                        <td><input type="number" name="amount_sanctioned[]" class="form-control" value="{{ old('amount_sanctioned.'.$index, $budget->amount_sanctioned ?? 0.00) }}" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
                        <td><input type="number" name="total_amount[]" class="form-control" value="{{ old('total_amount.'.$index, ($budget->amount_forwarded ?? 0.00) + ($budget->amount_sanctioned ?? 0.00)) }}" readonly></td>
                        <td><input type="number" name="expenses_last_month[]" class="form-control" value="{{ old('expenses_last_month.'.$index, $lastExpenses[$budget->iies_particular] ?? 0.00) }}" readonly></td>
                        <td><input type="number" name="expenses_this_month[]" class="form-control" value="{{ old('expenses_this_month.'.$index, 0.00) }}" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="total_expenses[]" class="form-control" readonly></td>
                        <td><input type="number" name="balance_amount[]" class="form-control" readonly></td>
                        <td>
                            <span class="badge bg-info">Budget Row</span>
                        </td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th><input type="number" id="total_forwarded" class="form-control" readonly></th>
                    <th><input type="number" id="total_sanctioned" class="form-control" readonly></th>
                    <th><input type="number" id="total_amount_total" class="form-control" readonly></th>
                    <th><input type="number" id="total_expenses_last_month" class="form-control" readonly></th>
                    <th><input type="number" id="total_expenses_this_month" class="form-control" readonly></th>
                    <th><input type="number" id="total_expenses_total" class="form-control" readonly></th>
                    <th><input type="number" id="total_balance" class="form-control" readonly></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
        <button type="button" class="btn btn-primary" onclick="addAccountRow()">Add Additional Expense Row</button>

        <div class="mt-3">
            <label for="total_balance_forwarded" class="form-label">Total Balance Amount Forwarded for the Following Month: Rs.</label>
            <input type="number" name="total_balance_forwarded" class="form-control readonly-input" value="{{ old('total_balance_forwarded', $report->total_balance_forwarded ?? 0.00) }}" readonly>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Document is ready. Calculating total.');
    calculateAllRowTotals();
    calculateTotal();
    updateAllBalanceColors();
});

function calculateRowTotals(row) {
    console.log('Calculating row totals.');
    const amountForwarded = parseFloat(row.querySelector('[name="amount_forwarded[]"]').value) || 0;
    const amountSanctioned = parseFloat(row.querySelector('[name="amount_sanctioned[]"]').value) || 0;
    const expensesLastMonth = parseFloat(row.querySelector('[name="expenses_last_month[]"]').value) || 0;
    const expensesThisMonth = parseFloat(row.querySelector('[name="expenses_this_month[]"]').value) || 0;

    const totalAmount = amountForwarded + amountSanctioned;
    const totalExpenses = expensesLastMonth + expensesThisMonth;
    const balanceAmount = totalAmount - totalExpenses;

    row.querySelector('[name="total_amount[]"]').value = totalAmount.toFixed(2);
    row.querySelector('[name="total_expenses[]"]').value = totalExpenses.toFixed(2);
    row.querySelector('[name="balance_amount[]"]').value = balanceAmount.toFixed(2);

    console.log('Row totals calculated:', { totalAmount, totalExpenses, balanceAmount });
    calculateTotal();
}

function calculateAllRowTotals() {
    const rows = document.querySelectorAll('#account-rows tr');
    rows.forEach(row => {
        calculateRowTotals(row);
    });
}

function calculateTotal() {
    console.log('Calculating total for all rows.');
    const rows = document.querySelectorAll('#account-rows tr');
    let totalForwarded = 0;
    let totalSanctioned = 0;
    let totalAmountTotal = 0;
    let totalExpensesLastMonth = 0;
    let totalExpensesThisMonth = 0;
    let totalExpensesTotal = 0;
    let totalBalance = 0;

    rows.forEach(row => {
        totalForwarded += parseFloat(row.querySelector('[name="amount_forwarded[]"]').value) || 0;
        totalSanctioned += parseFloat(row.querySelector('[name="amount_sanctioned[]"]').value) || 0;
        totalAmountTotal += parseFloat(row.querySelector('[name="total_amount[]"]').value) || 0;
        totalExpensesLastMonth += parseFloat(row.querySelector('[name="expenses_last_month[]"]').value) || 0;
        totalExpensesThisMonth += parseFloat(row.querySelector('[name="expenses_this_month[]"]').value) || 0;
        totalExpensesTotal += parseFloat(row.querySelector('[name="total_expenses[]"]').value) || 0;
        totalBalance += parseFloat(row.querySelector('[name="balance_amount[]"]').value) || 0;
    });

    document.getElementById('total_forwarded').value = totalForwarded.toFixed(2);
    document.getElementById('total_sanctioned').value = totalSanctioned.toFixed(2);
    document.getElementById('total_amount_total').value = totalAmountTotal.toFixed(2);
    document.getElementById('total_expenses_last_month').value = totalExpensesLastMonth.toFixed(2);
    document.getElementById('total_expenses_this_month').value = totalExpensesThisMonth.toFixed(2);
    document.getElementById('total_expenses_total').value = totalExpensesTotal.toFixed(2);
    document.getElementById('total_balance').value = totalBalance.toFixed(2);

    document.querySelector('[name="total_balance_forwarded"]').value = totalBalance.toFixed(2);

    console.log('Total calculations completed:', {
        totalForwarded,
        totalSanctioned,
        totalAmountTotal,
        totalExpensesLastMonth,
        totalExpensesThisMonth,
        totalExpensesTotal,
        totalBalance
    });
}

function updateBalanceColor(inputElement) {
    const value = parseFloat(inputElement.value) || 0;
    console.log('Checking balance:', value);

    if (value < 0) {
        inputElement.style.backgroundColor = 'red';
    } else {
        inputElement.style.backgroundColor = '';
    }
}

function updateAllBalanceColors() {
    const balanceFields = document.querySelectorAll('[name="balance_amount[]"], #total_balance');
    balanceFields.forEach(field => {
        updateBalanceColor(field);
    });
}

function addAccountRow() {
    const tableBody = document.getElementById('account-rows');
    const newRow = document.createElement('tr');
    const currentRowCount = tableBody.querySelectorAll('tr').length;

    newRow.setAttribute('data-row-type', 'additional');
    newRow.innerHTML = `
        <input type="hidden" name="is_budget_row[${currentRowCount}]" value="0">
        <td><input type="text" name="particulars[]" class="form-control" placeholder="Enter expense description" style="background-color: #202ba3;"></td>
        <td><input type="number" name="amount_forwarded[]" class="form-control" value="0" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
        <td><input type="number" name="amount_sanctioned[]" class="form-control" value="0" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
        <td><input type="number" name="total_amount[]" class="form-control" readonly></td>
        <td><input type="number" name="expenses_last_month[]" class="form-control" value="0" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
        <td><input type="number" name="expenses_this_month[]" class="form-control" value="0" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
        <td><input type="number" name="total_expenses[]" class="form-control" readonly></td>
        <td><input type="number" name="balance_amount[]" class="form-control" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeAccountRow(this)">Remove</button></td>
    `;

    newRow.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            const row = input.closest('tr');
            calculateRowTotals(row);
            calculateTotal();
        });
    });

    tableBody.appendChild(newRow);
    calculateRowTotals(newRow);
    console.log('New additional expense row added.');
}

function removeAccountRow(button) {
    const row = button.closest('tr');
    const rowType = row.getAttribute('data-row-type');

    if (rowType === 'budget') {
        alert('Budget rows cannot be removed. Only additional expense rows can be deleted.');
        return;
    }

    if (confirm('Are you sure you want to remove this additional expense row?')) {
        row.remove();
        calculateTotal();
        console.log('Additional expense row removed.');
    }
}
</script>

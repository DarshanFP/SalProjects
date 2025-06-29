{{-- resources/views/reports/monthly/partials/edit/statements_of_account.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>4. Statements of Account</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="account_period" class="form-label">Account Statement Period:</label>
            <div class="d-flex">
                <input type="date" name="account_period_start" class="form-control" value="{{ old('account_period_start', $report->account_period_start) }}" style="background-color: #202ba3;">
                <span class="mx-2">to</span>
                <input type="date" name="account_period_end" class="form-control" value="{{ old('account_period_end', $report->account_period_end) }}" style="background-color: #202ba3;">
            </div>
        </div>
        <div class="mb-3">
            <label for="amount_sanctioned_overview" class="form-label">Amount Sanctioned: Rs.</label>
            <input type="number" name="amount_sanctioned_overview" class="form-control readonly-input" value="{{ old('amount_sanctioned_overview', $report->amount_sanctioned_overview) }}" readonly>
        </div>
        <div class="mb-3">
            <label for="amount_forwarded_overview" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
            <input type="number" name="amount_forwarded_overview" class="form-control readonly-input" value="{{ old('amount_forwarded_overview', $report->amount_forwarded_overview) }}" readonly>
        </div>
        <div class="mb-3">
            <label for="amount_in_hand" class="form-label">Total Amount: Rs.</label>
            <input type="number" name="amount_in_hand" class="form-control readonly-input" value="{{ old('amount_in_hand', $report->amount_in_hand) }}" readonly>
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
                @foreach ($report->accountDetails as $index => $accountDetail)
                    <tr data-budget-row="{{ $accountDetail->is_budget_row ? 'true' : 'false' }}">
                        <input type="hidden" name="account_detail_id[{{$index}}]" value="{{ $accountDetail->account_detail_id }}">
                        <input type="hidden" name="is_budget_row[{{$index}}]" value="{{ $accountDetail->is_budget_row }}">
                        <td><input type="text" name="particulars[]" class="form-control" value="{{ old('particulars.' . $index, $accountDetail->particulars) }}" readonly></td>
                        <td><input type="number" name="amount_forwarded[]" class="form-control" value="{{ old('amount_forwarded.' . $index, $accountDetail->amount_forwarded) }}" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
                        <td><input type="number" name="amount_sanctioned[]" class="form-control" value="{{ old('amount_sanctioned.' . $index, $accountDetail->amount_sanctioned) }}" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
                        <td><input type="number" name="total_amount[]" class="form-control" value="{{ old('total_amount.' . $index, $accountDetail->amount_forwarded + $accountDetail->amount_sanctioned) }}" readonly></td>
                        <td><input type="number" name="expenses_last_month[]" class="form-control" value="{{ old('expenses_last_month.' . $index, $accountDetail->expenses_last_month) }}" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
                        <td><input type="number" name="expenses_this_month[]" class="form-control" value="{{ old('expenses_this_month.' . $index, $accountDetail->expenses_this_month) }}" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
                        <td><input type="number" name="total_expenses[]" class="form-control" value="{{ old('total_expenses.' . $index, $accountDetail->total_expenses) }}" readonly></td>
                        <td><input type="number" name="balance_amount[]" class="form-control" value="{{ old('balance_amount.' . $index, $accountDetail->balance_amount) }}" readonly></td>
                        <td>
                            @if($accountDetail->is_budget_row)
                                {{-- Hide remove button for budget rows from project budget --}}
                                <span class="text-muted">Budget Row</span>
                            @else
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeAccountRow(this)">Remove</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
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
        <button type="button" class="btn btn-primary" onclick="addAccountRow()">Add Row</button>

        <div class="mt-3">
            <label for="total_balance_forwarded" class="form-label">Total Balance Amount Forwarded for the Following Month: Rs.</label>
            <input type="number" name="total_balance_forwarded" class="form-control" readonly>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.querySelector('.table tbody');

    // Event listener for input changes in the table
    table.addEventListener('input', function (event) {
        const row = event.target.closest('tr');
        calculateRowTotals(row);
        calculateTotal();
    });

    const prjctAmountSanctioned = document.querySelector('[name="amount_sanctioned_overview"]');
    const lyAmountForwarded = document.querySelector('[name="amount_forwarded_overview"]');

    prjctAmountSanctioned.addEventListener('input', calculateTotalAmount);
    lyAmountForwarded.addEventListener('input', calculateTotalAmount);

    // Initialize calculations on page load
    calculateAllRowTotals();
    calculateTotal();
    calculateTotalAmount();
});

// Function to calculate totals for a single row
function calculateRowTotals(row) {
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

    calculateTotal(); // Update overall totals
}

// Function to calculate all row totals on page load
function calculateAllRowTotals() {
    const rows = document.querySelectorAll('#account-rows tr');
    rows.forEach(row => {
        calculateRowTotals(row);
    });
}

// Function to calculate the overall totals
function calculateTotal() {
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

    // Update the total balance forwarded field
    document.querySelector('[name="total_balance_forwarded"]').value = totalBalance.toFixed(2);
}

// Function to calculate the overall amount in hand
function calculateTotalAmount() {
    const amountSanctioned = parseFloat(document.querySelector('[name="amount_sanctioned_overview"]').value) || 0;
    const amountForwarded = parseFloat(document.querySelector('[name="amount_forwarded_overview"]').value) || 0;
    const totalAmount = amountSanctioned + amountForwarded;

    document.querySelector('[name="amount_in_hand"]').value = totalAmount.toFixed(2);
}

// Function to add a new row in the account table
function addAccountRow() {
    const tableBody = document.getElementById('account-rows');
    const newRow = document.createElement('tr');
    const currentRowCount = tableBody.querySelectorAll('tr').length;
    newRow.innerHTML = `
        <input type="hidden" name="account_detail_id[${currentRowCount}]" value="">
        <input type="hidden" name="is_budget_row[${currentRowCount}]" value="0">
        <td><input type="text" name="particulars[]" class="form-control" style="background-color: #202ba3;"></td>
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
        input.addEventListener('input', function () {
            const row = input.closest('tr');
            calculateRowTotals(row);
            calculateTotal();
        });
    });
    tableBody.appendChild(newRow);
    calculateRowTotals(newRow);
}

// Function to remove a row from the account table
function removeAccountRow(button) {
    const row = button.closest('tr');
    row.remove();
    calculateTotal(); // Recalculate totals after removing a row
}

</script>

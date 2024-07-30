<div class="mb-3 card">
    <div class="card-header">
        <h4>4. Statements of Account</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="account_period" class="form-label">Account Statement Period:</label>
            <div class="d-flex">
                <input type="date" name="account_period_start" class="form-control @error('account_period_start') is-invalid @enderror" style="background-color: #202ba3;">
                @error('account_period_start')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <span class="mx-2">to</span>
                <input type="date" name="account_period_end" class="form-control @error('account_period_end') is-invalid @enderror" style="background-color: #202ba3;">
                @error('account_period_end')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="mb-3">
            <label for="amount_sanctioned_overview" class="form-label">Amount Sanctioned: Rs.</label>
            <input type="number" name="amount_sanctioned_overview" class="form-control" value="{{ old('amount_sanctioned_overview', $amountSanctioned ?? 0.00) }}" readonly>
        </div>
        <div class="mb-3">
            <label for="amount_forwarded_overview" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
            <input type="number" name="amount_forwarded_overview" class="form-control" value="{{ old('amount_forwarded_overview', $amountForwarded ?? 0.00) }}" style="background-color: #202ba3;">
        </div>
        <div class="mb-3">
            <label for="amount_in_hand" class="form-label">Total Amount: Rs.</label>
            <input type="number" name="amount_in_hand" class="form-control readonly-input" value="{{ old('amount_in_hand', ($amountSanctioned ?? 0.00) + ($amountForwarded ?? 0.00)) }}" readonly>
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
                @foreach($budgets as $index => $budget)
                <tr>
                    <td><input type="text" name="particulars[]" class="form-control" value="{{ old('particulars.'.$index, $budget->particular) }}" readonly></td>
                    <td><input type="number" name="amount_forwarded[]" class="form-control" value="{{ old('amount_forwarded.'.$index, 0.00) }}" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #6571ff;"></td>
                    <td><input type="number" name="amount_sanctioned[]" class="form-control" value="{{ old('amount_sanctioned.'.$index, $budget->this_phase ?? 0.00) }}" oninput="calculateRowTotals(this.closest('tr'))" readonly></td>
                    <td><input type="number" name="total_amount[]" class="form-control" value="{{ old('total_amount.'.$index, ($budget->amount_forwarded ?? 0.00) + ($budget->this_phase ?? 0.00)) }}" readonly></td>
                    <td><input type="number" name="expenses_last_month[]" class="form-control" value="{{ old('expenses_last_month.'.$index, $lastExpenses[$budget->particular] ?? 0.00) }}" readonly></td>
                    <td><input type="number" name="expenses_this_month[]" class="form-control" value="{{ old('expenses_this_month.'.$index, 0.00) }}" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
                    <td><input type="number" name="total_expenses[]" class="form-control" readonly></td>
                    <td><input type="number" name="balance_amount[]" class="form-control" readonly></td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeAccountRow(this)">Remove</button></td>
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
            <input type="number" name="total_balance_forwarded" class="form-control readonly-input" value="{{ old('total_balance_forwarded', 0.00) }}" readonly>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
    console.log('Document is ready. Calculating total.');
    calculateTotal();
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

function addAccountRow() {
    const tableBody = document.getElementById('account-rows');
    const newRow = document.createElement('tr');

    newRow.innerHTML = `
        <td><input type="text" name="particulars[]" class="form-control" style="background-color: #202ba3;"></td>
        <td><input type="number" name="amount_forwarded[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
        <td><input type="number" name="amount_sanctioned[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
        <td><input type="number" name="total_amount[]" class="form-control" readonly></td>
        <td><input type="number" name="expenses_last_month[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
        <td><input type="number" name="expenses_this_month[]" class="form-control" oninput="calculateRowTotals(this.closest('tr'))" style="background-color: #202ba3;"></td>
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
    console.log('New row added.');
}

function removeAccountRow(button) {
    const row = button.closest('tr');
    row.remove();
    calculateTotal();
    console.log('Row removed.');
}

</script>

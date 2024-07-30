<div class="mb-3 card">
    <div class="card-header">
        <h4>4. Statements of Account</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="account_period" class="form-label">Account Statement Period:</label>
            <div class="d-flex">
                <input type="date" name="account_period_start" class="form-control" style="background-color: #6571ff;">
                <span class="mx-2">to</span>
                <input type="date" name="account_period_end" class="form-control" style="background-color: #6571ff;">
            </div>
        </div>
        <div class="mb-3">
            <label for="amount_sanctioned_overview" class="form-label">Amount Sanctioned: Rs.</label>
            <input type="number" name="amount_sanctioned_overview" class="form-control readonly-input" value="{{ $amountSanctioned }}" readonly>
        </div>
        <div class="mb-3">
            <label for="amount_forwarded_overview" class="form-label">Amount Forwarded from the Last Financial Year: Rs.</label>
            <input type="number" name="amount_forwarded_overview" class="form-control readonly-input" value="{{ $amountForwarded }}" readonly>
        </div>
        <div class="mb-3">
            <label for="amount_in_hand" class="form-label">Total Amount: Rs.</label>
            <input type="number" name="amount_in_hand" class="form-control readonly-input" value="{{ $amountSanctioned + $amountForwarded }}" readonly>
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
                    <td><input type="text" name="particulars[]" class="form-control" value="{{ $budget->particular }}" style="background-color: #6571ff;"></td>
                    <td><input type="number" name="amount_forwarded[]" class="form-control" value="{{ old('amount_forwarded.'.$index) }}" oninput="calculateRowTotals(this)" style="background-color: #6571ff;"></td>
                    <td><input type="number" name="amount_sanctioned[]" class="form-control" value="{{ $budget->this_phase }}" oninput="calculateRowTotals(this)" readonly></td>
                    <td><input type="number" name="total_amount[]" class="form-control" value="{{ $budget->amount_forwarded + $budget->this_phase }}" readonly></td>
                    <td><input type="number" name="expenses_last_month[]" class="form-control" value="{{ $lastExpenses->get($budget->particular, 0) }}" readonly></td>
                    <td><input type="number" name="expenses_this_month[]" class="form-control" oninput="calculateRowTotals(this)" style="background-color: #6571ff;"></td>
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
            <input type="number" name="total_balance_forwarded" class="form-control" readonly>
        </div>
    </div>
</div>

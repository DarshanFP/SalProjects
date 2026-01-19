{{-- resources/views/projects/partials/IES/estimated_expenses.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Estimated Expenses (give full details)</h4>
    </div>
    <div class="card-body">
        <!-- Estimated Expenses Table -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th>Particular</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="IES-expenses-table">
                    <tr>
                        <td style="text-align: center; vertical-align: middle;">1</td>
                        <td><input type="text" name="particulars[]" class="form-control"></td>
                        <td><input type="number" name="amounts[]" class="form-control IES-expense-input" step="0.01" oninput="IEScalculateTotalExpenses()"></td>
                        <td><button type="button" class="btn btn-danger" onclick="IESremoveExpenseRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="mt-2 btn btn-primary" onclick="IESaddExpenseRow()">Add More</button>
        </div>

        <!-- Total Expense -->
        <div class="mt-3 form-group">
            <label>Total expense of the study:</label>
            <input type="number" name="total_expenses" class="form-control" step="0.01" readonly>
        </div>

        <!-- Financial Contributions -->
        <div class="form-group">
            <label>Scholarship expected from government:</label>
            <input type="number" name="expected_scholarship_govt" class="form-control" step="0.01" oninput="IEScalculateBalanceRequested()">
        </div>
        <div class="form-group">
            <label>Support from other sources:</label>
            <input type="number" name="support_other_sources" class="form-control" step="0.01" oninput="IEScalculateBalanceRequested()">
        </div>
        <div class="form-group">
            <label>Beneficiaries’ contribution:</label>
            <input type="number" name="beneficiary_contribution" class="form-control" step="0.01" oninput="IEScalculateBalanceRequested()">
        </div>

        <!-- Balance Amount Requested -->
        <div class="form-group">
            <label>Balance amount requested:</label>
            <input type="number" name="balance_requested" class="form-control" step="0.01" readonly>
        </div>
    </div>
</div>

<!-- JavaScript to manage table rows and calculate totals -->
<script>
    // Function to add a new expense row
    function IESaddExpenseRow() {
        const table = document.querySelector('#IES-expenses-table');
        const rowCount = table.children.length;
        const row = `
            <tr>
                <td style="text-align: center; vertical-align: middle;">${rowCount + 1}</td>
                <td><input type="text" name="particulars[]" class="form-control"></td>
                <td><input type="number" name="amounts[]" class="form-control IES-expense-input" step="0.01" oninput="IEScalculateTotalExpenses()"></td>
                <td><button type="button" class="btn btn-danger" onclick="IESremoveExpenseRow(this)">Remove</button></td>
            </tr>`;
        table.insertAdjacentHTML('beforeend', row);
        reindexIESExpenseRows();
    }

    // Function to remove an expense row
    function IESremoveExpenseRow(button) {
        button.closest('tr').remove();
        reindexIESExpenseRows();
        IEScalculateTotalExpenses();
    }
    
    // Reindex expense rows
    function reindexIESExpenseRows() {
        const rows = document.querySelectorAll('#IES-expenses-table tr');
        rows.forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
    }

    // Function to calculate total expenses
    function IEScalculateTotalExpenses() {
        let totalExpenses = 0;
        document.querySelectorAll('.IES-expense-input').forEach(input => {
            totalExpenses += parseFloat(input.value) || 0;
        });
        document.querySelector('input[name="total_expenses"]').value = totalExpenses.toFixed(2);
        IEScalculateBalanceRequested();
    }

    // Function to calculate balance requested
    function IEScalculateBalanceRequested() {
        const totalExpenses = parseFloat(document.querySelector('input[name="total_expenses"]').value) || 0;
        const scholarship = parseFloat(document.querySelector('input[name="expected_scholarship_govt"]').value) || 0;
        const otherSources = parseFloat(document.querySelector('input[name="support_other_sources"]').value) || 0;
        const contribution = parseFloat(document.querySelector('input[name="beneficiary_contribution"]').value) || 0;
        const balanceRequested = totalExpenses - (scholarship + otherSources + contribution);
        document.querySelector('input[name="balance_requested"]').value = balanceRequested.toFixed(2);

        // ALSO update the #overall_project_budget if it exists
        const overallBudget = document.getElementById('overall_project_budget');
        if (overallBudget) {
            overallBudget.value = balanceRequested.toFixed(2);
        }
    }
</script>

<!-- Styles -->
<style>
    .form-control {
        
        color: white;
    }
</style>

{{-- resources/views/projects/partials/Edit/IES/estimated_expenses.blade.php
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Estimated Expenses (Give Full Details)</h4>
    </div>
    <div class="card-body">
        <!-- Estimated Expenses Table -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Particular</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="IES-expenses-table">
                    @if(optional($project->iesExpenses)->count())
                        @foreach($project->iesExpenses as $index => $expense)
                        <tr>
                            <td><input type="text" name="particulars[]" class="form-control" value="{{ old('particulars')[$index] ?? $expense->particular }}" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="amounts[]" class="form-control IES-expense-input" step="0.01" value="{{ old('amounts')[$index] ?? $expense->amount }}" style="background-color: #202ba3;" oninput="IEScalculateTotalExpenses()"></td>
                            <td><button type="button" class="btn btn-danger" onclick="IESremoveExpenseRow(this)">Remove</button></td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td><input type="text" name="particulars[]" class="form-control" style="background-color: #202ba3;"></td>
                            <td><input type="number" name="amounts[]" class="form-control IES-expense-input" step="0.01" style="background-color: #202ba3;" oninput="IEScalculateTotalExpenses()"></td>
                            <td><button type="button" class="btn btn-danger" onclick="IESremoveExpenseRow(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <button type="button" class="mt-2 btn btn-primary" onclick="IESaddExpenseRow()">Add More</button>
        </div>

        <!-- Total Expense -->
        <div class="mt-3 form-group">
            <label>Total expense of the study:</label>
            <input type="number" name="total_expenses" class="form-control" step="0.01" value="{{ old('total_expenses', optional($project->iesExpenses)->sum('amount')) }}" style="background-color: #202ba3;" readonly>
        </div>

        <!-- Financial Contributions -->
        <div class="form-group">
            <label>Scholarship expected from government:</label>
            <input type="number" name="expected_scholarship_govt" class="form-control" step="0.01" value="{{ old('expected_scholarship_govt', $project->expected_scholarship_govt) }}" style="background-color: #202ba3;" oninput="IEScalculateBalanceRequested()">
        </div>
        <div class="form-group">
            <label>Support from other sources:</label>
            <input type="number" name="support_other_sources" class="form-control" step="0.01" value="{{ old('support_other_sources', $project->support_other_sources) }}" style="background-color: #202ba3;" oninput="IEScalculateBalanceRequested()">
        </div>
        <div class="form-group">
            <label>Beneficiaries’ contribution:</label>
            <input type="number" name="beneficiary_contribution" class="form-control" step="0.01" value="{{ old('beneficiary_contribution', $project->beneficiary_contribution) }}" style="background-color: #202ba3;" oninput="IEScalculateBalanceRequested()">
        </div>

        <!-- Balance Amount Requested -->
        <div class="form-group">
            <label>Balance amount requested:</label>
            <input type="number" name="balance_requested" class="form-control" step="0.01" value="{{ old('balance_requested', $project->balance_requested) }}" style="background-color: #202ba3;" readonly>
        </div>
    </div>
</div>

<!-- JavaScript to manage table rows and calculate totals -->
<script>
    function IESaddExpenseRow() {
        const row = `
            <tr>
                <td><input type="text" name="particulars[]" class="form-control" style="background-color: #202ba3;"></td>
                <td><input type="number" name="amounts[]" class="form-control IES-expense-input" step="0.01" style="background-color: #202ba3;" oninput="IEScalculateTotalExpenses()"></td>
                <td><button type="button" class="btn btn-danger" onclick="IESremoveExpenseRow(this)">Remove</button></td>
            </tr>`;
        document.querySelector('#IES-expenses-table').insertAdjacentHTML('beforeend', row);
        IEScalculateTotalExpenses();
    }

    function IESremoveExpenseRow(button) {
        if (document.querySelectorAll('#IES-expenses-table tr').length > 1) {
            button.closest('tr').remove();
            IEScalculateTotalExpenses();
        } else {
            alert("At least one expense entry is required.");
        }
    }

    function IEScalculateTotalExpenses() {
        let totalExpenses = 0;
        document.querySelectorAll('.IES-expense-input').forEach(input => {
            totalExpenses += parseFloat(input.value) || 0;
        });
        document.querySelector('input[name="total_expenses"]').value = totalExpenses.toFixed(2);
        IEScalculateBalanceRequested();
    }

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
        background-color: #202ba3;
        color: white;
    }
</style> --}}
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
                        <th>Particular</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="IES-expenses-table">
                    @if($IESExpenses && $IESExpenses->expenseDetails->count())
                        @foreach($IESExpenses->expenseDetails as $expense)
                            <tr>
                                <td><input type="text" name="particulars[]" class="form-control"
                                           value="{{ old('particulars.' . $loop->index, $expense->particular) }}"></td>
                                <td><input type="number" name="amounts[]" class="form-control IES-expense-input" step="0.01"
                                           value="{{ old('amounts.' . $loop->index, $expense->amount) }}"
                                           oninput="IEScalculateTotalExpenses()"></td>
                                <td><button type="button" class="btn btn-danger" onclick="IESremoveExpenseRow(this)">Remove</button></td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
            <button type="button" class="mt-2 btn btn-primary" onclick="IESaddExpenseRow()">Add More</button>
        </div>

        <!-- Total Expense -->
        <div class="mt-3 form-group">
            <label>Total expense of the study:</label>
            <input type="number" name="total_expenses" class="form-control" step="0.01"
                   value="{{ old('total_expenses', $IESExpenses->total_expenses ?? '') }}" readonly>
        </div>

        <!-- Financial Contributions -->
        <div class="form-group">
            <label>Scholarship expected from government:</label>
            <input type="number" name="expected_scholarship_govt" class="form-control" step="0.01"
                   value="{{ old('expected_scholarship_govt', $IESExpenses->expected_scholarship_govt ?? '') }}"
                   oninput="IEScalculateBalanceRequested()">
        </div>
        <div class="form-group">
            <label>Support from other sources:</label>
            <input type="number" name="support_other_sources" class="form-control" step="0.01"
                   value="{{ old('support_other_sources', $IESExpenses->support_other_sources ?? '') }}"
                   oninput="IEScalculateBalanceRequested()">
        </div>
        <div class="form-group">
            <label>Beneficiaries’ contribution:</label>
            <input type="number" name="beneficiary_contribution" class="form-control" step="0.01"
                   value="{{ old('beneficiary_contribution', $IESExpenses->beneficiary_contribution ?? '') }}"
                   oninput="IEScalculateBalanceRequested()">
        </div>

        <!-- Balance Amount Requested -->
        <div class="form-group">
            <label>Balance amount requested:</label>
            <input type="number" name="balance_requested" class="form-control" step="0.01"
                   value="{{ old('balance_requested', $IESExpenses->balance_requested ?? '') }}" readonly>
        </div>
    </div>
</div>

<!-- JavaScript to manage table rows and calculate totals -->
<script>
    function IESaddExpenseRow() {
        const row = `
            <tr>
                <td><input type="text" name="particulars[]" class="form-control"></td>
                <td><input type="number" name="amounts[]" class="form-control IES-expense-input" step="0.01"
                           oninput="IEScalculateTotalExpenses()"></td>
                <td><button type="button" class="btn btn-danger" onclick="IESremoveExpenseRow(this)">Remove</button></td>
            </tr>`;
        document.querySelector('#IES-expenses-table').insertAdjacentHTML('beforeend', row);
    }

    function IESremoveExpenseRow(button) {
        button.closest('tr').remove();
        IEScalculateTotalExpenses();
    }

    function IEScalculateTotalExpenses() {
        let totalExpenses = 0;
        document.querySelectorAll('.IES-expense-input').forEach(input => {
            totalExpenses += parseFloat(input.value) || 0;
        });
        document.querySelector('input[name="total_expenses"]').value = totalExpenses.toFixed(2);
        IEScalculateBalanceRequested();
    }

    function IEScalculateBalanceRequested() {
        const totalExpenses = parseFloat(document.querySelector('input[name="total_expenses"]').value) || 0;
        const scholarship = parseFloat(document.querySelector('input[name="expected_scholarship_govt"]').value) || 0;
        const otherSources = parseFloat(document.querySelector('input[name="support_other_sources"]').value) || 0;
        const contribution = parseFloat(document.querySelector('input[name="beneficiary_contribution"]').value) || 0;
        const balanceRequested = totalExpenses - (scholarship + otherSources + contribution);
        document.querySelector('input[name="balance_requested"]').value = balanceRequested.toFixed(2);
    }
</script>

<!-- Styles -->
<style>
    .form-control {
        background-color: #202ba3;
        color: white;
    }
</style>
